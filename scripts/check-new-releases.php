<?php

/**
 * Supply-chain safety net for composer.lock changes, mirroring the pnpm
 * policy used on our JS projects (see the fake typescript@7.0.2 Dependabot
 * PR that prompted this). Composer has no resolver-level "minimum release
 * age" the way pnpm does, so this only runs as a CI gate on PRs — it does
 * NOT stop a fresh `composer update` on a developer machine.
 *
 * For every package version newly introduced by this PR's composer.lock,
 * checks two things via the Packagist API:
 *   1. Release age — flags versions tagged more recently than
 *      MINIMUM_RELEASE_AGE_MINUTES ago. Malicious/compromised releases are
 *      typically caught and pulled within hours to a couple of days.
 *   2. Source repository match — flags versions whose composer.lock
 *      "source" URL no longer matches the repository Packagist currently
 *      has on file for that package, which can indicate the Packagist
 *      listing was repointed at an unofficial/hijacked repo.
 *
 * Heuristics, not proof — a flag means "verify before merging", not
 * "certain compromise".
 */

$failOnWarning = getenv('FAIL_ON_NEW_RELEASE') !== 'false';
$baseRef = getenv('BASE_REF') ?: 'origin/main';
$minimumReleaseAgeMinutes = (int) (getenv('MINIMUM_RELEASE_AGE_MINUTES') ?: 4320); // 3 days

function loadLockedPackages(string $json): array
{
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [];
    }
    $packages = [];
    foreach (['packages', 'packages-dev'] as $key) {
        foreach ($data[$key] ?? [] as $pkg) {
            $packages[$pkg['name']] = [
                'version' => $pkg['version'],
                'source' => $pkg['source']['url'] ?? null,
            ];
        }
    }
    return $packages;
}

function getBaseLockfile(string $baseRef): ?string
{
    $output = [];
    $exitCode = 0;
    exec('git show ' . escapeshellarg($baseRef . ':composer.lock') . ' 2>/dev/null', $output, $exitCode);
    if ($exitCode !== 0) {
        return null;
    }
    return implode("\n", $output);
}

function fetchJson(string $url): ?array
{
    $context = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

function checkPackage(string $name, string $version, ?string $lockedSource): array
{
    [$vendor, $pkg] = explode('/', $name, 2) + [1 => null];
    if ($pkg === null) {
        return ['skip' => "not a vendor/package name"];
    }

    $p2 = fetchJson("https://repo.packagist.org/p2/{$vendor}/{$pkg}.json");
    $releases = $p2['packages']["{$vendor}/{$pkg}"] ?? null;
    if ($releases === null) {
        return ['skip' => 'not found on Packagist (private/unlisted package)'];
    }

    $release = null;
    foreach ($releases as $r) {
        if ($r['version'] === $version) {
            $release = $r;
            break;
        }
    }
    if ($release === null) {
        return ['skip' => 'version not found in Packagist metadata'];
    }

    $warnings = [];

    if (!empty($release['time'])) {
        $ageMinutes = (time() - strtotime($release['time'])) / 60;
        global $minimumReleaseAgeMinutes;
        if ($ageMinutes < $minimumReleaseAgeMinutes) {
            $days = round($ageMinutes / 1440, 1);
            $warnings[] = "{$name}@{$version} was released {$days} day(s) ago, inside the "
                . round($minimumReleaseAgeMinutes / 1440, 1) . "-day cooldown window.";
        }
    }

    if ($lockedSource) {
        $meta = fetchJson("https://packagist.org/packages/{$vendor}/{$pkg}.json");
        $registeredRepo = $meta['package']['repository'] ?? null;
        if ($registeredRepo) {
            $normalize = fn ($u) => rtrim(preg_replace('#^git\+|\.git$#', '', $u), '/');
            if ($normalize($registeredRepo) !== $normalize($lockedSource)) {
                $warnings[] = "{$name}@{$version} source ({$lockedSource}) does not match the repository "
                    . "Packagist currently has on file ({$registeredRepo}) — the package listing may have "
                    . "been repointed to a different repo.";
            }
        }
    }

    return ['warnings' => $warnings];
}

function main(): void
{
    global $baseRef, $failOnWarning;

    $headText = file_get_contents('composer.lock');
    $baseText = getBaseLockfile($baseRef);

    $headPackages = loadLockedPackages($headText);
    $basePackages = $baseText ? loadLockedPackages($baseText) : [];

    $changed = [];
    foreach ($headPackages as $name => $info) {
        $base = $basePackages[$name] ?? null;
        if ($base === null || $base['version'] !== $info['version'] || $base['source'] !== $info['source']) {
            $changed[$name] = $info;
        }
    }

    if (empty($changed)) {
        echo "No new/changed package versions introduced — nothing to check.\n";
        return;
    }

    echo 'Checking release age and source integrity for ' . count($changed) . " changed package(s)...\n";

    $allWarnings = [];
    foreach ($changed as $name => $info) {
        $result = checkPackage($name, $info['version'], $info['source']);
        if (!empty($result['skip'])) {
            echo "  skip {$name}@{$info['version']}: {$result['skip']}\n";
            continue;
        }
        foreach ($result['warnings'] as $w) {
            $allWarnings[] = $w;
        }
    }

    if (!empty($allWarnings)) {
        echo "\nPotential supply-chain concerns detected:\n\n";
        foreach ($allWarnings as $w) {
            echo "::warning file=composer.lock::{$w}\n";
        }
        echo "\nVerify each flagged release against the package's official GitHub releases/changelog "
            . "before merging.\n";
        if ($failOnWarning) {
            echo "\nFailing the check (set FAIL_ON_NEW_RELEASE=false to warn without blocking).\n";
            exit(1);
        }
    } else {
        echo "No concerns detected.\n";
    }
}

main();
