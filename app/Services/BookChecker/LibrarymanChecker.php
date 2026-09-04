<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use Illuminate\Support\Str;

/** Availability parser for Libraryman (libraryman.se) product pages. */
class LibrarymanChecker implements CheckerInterface
{
    public function supports(string $url): bool
    {
        return Str::contains($url, 'libraryman');
    }

    /**
     * Available if the page shows a euro price (e.g. "€50") or a "btn-add"
     * cart link that isn't disabled.
     */
    public function check(string $pageContent, string $url): BookStatus
    {
        if (preg_match('/(?:€|&euro;)\s?\d/', $pageContent) === 1) {
            return BookStatus::Available;
        }

        preg_match_all('/<a[^>]*class="[^"]*btn-add[^"]*"[^>]*>/i', $pageContent, $matches);

        foreach ($matches[0] as $button) {
            if (! Str::contains($button, 'disabled')) {
                return BookStatus::Available;
            }
        }

        return BookStatus::Unavailable;
    }
}
