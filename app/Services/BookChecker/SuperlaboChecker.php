<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use Illuminate\Support\Str;

/** Availability parser for Superlabo (superlabo.com) Shopify product pages. */
class SuperlaboChecker implements CheckerInterface
{
    public function supports(string $url): bool
    {
        return Str::contains($url, 'superlabo');
    }

    /**
     * Available if the page shows a dollar price (e.g. "$53.00") or a
     * "product__add-to-cart" button that isn't disabled. Text elsewhere on
     * the page (e.g. JS strings like "Sold out") is ignored — only the
     * button element itself is checked.
     */
    public function check(string $pageContent, string $url): BookStatus
    {
        if (preg_match('/\$\s?\d/', $pageContent) === 1) {
            return BookStatus::Available;
        }

        preg_match_all('/<button[^>]*class="[^"]*product__add-to-cart[^"]*"[^>]*>/i', $pageContent, $matches);

        foreach ($matches[0] as $button) {
            if (! Str::contains($button, 'disabled')) {
                return BookStatus::Available;
            }
        }

        return BookStatus::Unavailable;
    }
}
