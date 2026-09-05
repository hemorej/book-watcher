<?php

namespace App\Services\SecondarySource;

use App\Enums\BookStatus;
use App\Support\TitleMatcher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The Polygon Gallery bookstore (store.thepolygon.ca), a Shopify shop.
 *
 * The search-results page renders "Sold out"/"Sale" badge markup on every
 * card regardless of actual stock (it's theme chrome, toggled client-side),
 * so it's only used to find the best-matching product's title and URL. The
 * matched product's own page is then fetched, where the add-to-cart button's
 * text/aria-label does reflect real availability.
 */
class PolygonSecondarySource implements SecondarySource
{
    private const BASE_URL = 'https://store.thepolygon.ca';

    /** Below this title-similarity score a result is noise, not a candidate. */
    private const MIN_SCORE = 40.0;

    public function name(): string
    {
        return 'The Polygon';
    }

    public function search(string $title, string $author): ?SecondarySourceMatch
    {
        $searchUrl = self::BASE_URL.'/search?'.http_build_query(['q' => $title, 'type' => 'product']);

        $html = $this->fetch($searchUrl, 'search');

        if ($html === null) {
            return null;
        }

        preg_match_all(
            '/<a class="full-width-link" href="([^"?]+)[^"]*">\s*<span class="visually-hidden">([^<]+)<\/span>/',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        $best = null;

        foreach ($matches as [, $path, $candidateTitle]) {
            $score = TitleMatcher::score($title, html_entity_decode($candidateTitle));

            if ($score < self::MIN_SCORE || ($best !== null && $score <= $best['score'])) {
                continue;
            }

            $best = ['score' => $score, 'url' => self::BASE_URL.$path];
        }

        if ($best === null) {
            return null;
        }

        $productHtml = $this->fetch($best['url'], 'product');

        if ($productHtml === null) {
            return null;
        }

        return new SecondarySourceMatch(
            source: $this->name(),
            url: $best['url'],
            status: $this->availabilityFrom($productHtml),
            confidence: $best['score'],
        );
    }

    private function availabilityFrom(string $productHtml): BookStatus
    {
        if (! preg_match('/<button[^>]*name="add"[^>]*>.*?<\/button>/is', $productHtml, $match)) {
            return BookStatus::Unsure;
        }

        if (Str::contains($match[0], 'sold out', ignoreCase: true)) {
            return BookStatus::Unavailable;
        }

        return BookStatus::Available;
    }

    private function fetch(string $url, string $step): ?string
    {
        try {
            $response = Http::withUserAgent(
                'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            )->timeout(15)->get($url);
        } catch (\Throwable $e) {
            Log::warning('secondary_source.polygon.request_failed', ['step' => $step, 'url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('secondary_source.polygon.non_200', ['step' => $step, 'url' => $url, 'status' => $response->status()]);

            return null;
        }

        return $response->body();
    }
}
