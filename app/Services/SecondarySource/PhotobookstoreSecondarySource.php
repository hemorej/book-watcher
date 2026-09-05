<?php

namespace App\Services\SecondarySource;

use App\Enums\BookStatus;
use App\Support\TitleMatcher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Photobook Store (photobookstore.co.uk), a Shopify shop on an older theme
 * than {@see PolygonSecondarySource}'s — different search-results markup,
 * but the same two-step approach: search for the best-matching title/URL,
 * then fetch that product page for its real add-to-cart button state.
 */
class PhotobookstoreSecondarySource implements SecondarySource
{
    private const BASE_URL = 'https://photobookstore.co.uk';

    /** Below this title-similarity score a result is noise, not a candidate. */
    private const MIN_SCORE = 40.0;

    public function name(): string
    {
        return 'Photobook Store';
    }

    public function search(string $title, string $author): ?SecondarySourceMatch
    {
        $searchUrl = self::BASE_URL.'/search?'.http_build_query(['q' => $title, 'type' => 'product']);

        $html = $this->fetch($searchUrl, 'search');

        if ($html === null) {
            return null;
        }

        preg_match_all(
            '/<a href="([^"?]+)[^"]*" class="grid-product__link[^"]*">(.*?)<div class="grid-product__title grid-product__title--body">([^<]+)<\/div>/s',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        $best = null;

        foreach ($matches as [, $path, , $candidateTitle]) {
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
        if (! preg_match('/<button\s+type="submit"\s+name="add"[^>]*>.*?<\/button>/is', $productHtml, $match)) {
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
            Log::warning('secondary_source.photobookstore.request_failed', ['step' => $step, 'url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('secondary_source.photobookstore.non_200', ['step' => $step, 'url' => $url, 'status' => $response->status()]);

            return null;
        }

        return $response->body();
    }
}
