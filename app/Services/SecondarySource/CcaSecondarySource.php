<?php

namespace App\Services\SecondarySource;

use App\Enums\BookStatus;
use App\Support\TitleMatcher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Canadian Centre for Architecture bookstore search (cca.qc.ca).
 *
 * Each result embeds a `data-info` JSON blob (title, ISBN, price) on its "Buy
 * book" link, followed a little further down by the literal text
 * "(available in store)" when in stock. There's no stable per-book product
 * URL, so the match links back to the search results page itself.
 */
class CcaSecondarySource implements SecondarySource
{
    private const BASE_URL = 'https://www.cca.qc.ca/en/search';

    /** Below this title-similarity score a result is noise, not a candidate. */
    private const MIN_SCORE = 40.0;

    /** How far past a `data-info` blob to look for the "(available in store)" marker belonging to it. */
    private const AVAILABILITY_WINDOW = 2000;

    public function name(): string
    {
        return 'CCA Bookstore';
    }

    public function search(string $title, string $author): ?SecondarySourceMatch
    {
        $url = self::BASE_URL.'?'.http_build_query([
            'query' => $title,
            'filters' => json_encode(['forms-bookstore-all' => ['bookstore-all']]),
        ]);

        try {
            $response = Http::withUserAgent(
                'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            )->timeout(15)->get($url);
        } catch (\Throwable $e) {
            Log::warning('secondary_source.cca.request_failed', ['title' => $title, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('secondary_source.cca.non_200', ['title' => $title, 'status' => $response->status()]);

            return null;
        }

        $body = $response->body();

        preg_match_all('/data-info="([^"]+)"/', $body, $matches, PREG_OFFSET_CAPTURE);

        $best = null;

        foreach ($matches[1] as [$rawInfo, $offset]) {
            $info = json_decode(html_entity_decode($rawInfo, ENT_QUOTES), true);

            if (! is_array($info) || ! isset($info['title'])) {
                continue;
            }

            $score = TitleMatcher::score($title, $info['title']);

            if ($score < self::MIN_SCORE || ($best !== null && $score <= $best['score'])) {
                continue;
            }

            $window = substr($body, $offset, self::AVAILABILITY_WINDOW);

            $best = [
                'score' => $score,
                'available' => Str::contains($window, '(available in store)'),
            ];
        }

        if ($best === null) {
            return null;
        }

        return new SecondarySourceMatch(
            source: $this->name(),
            url: $url,
            status: $best['available'] ? BookStatus::Available : BookStatus::Unavailable,
            confidence: $best['score'],
        );
    }
}
