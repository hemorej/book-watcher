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
     * Shopify renders the add-to-cart button as "Sold out" when a variant is
     * unavailable, "Add to cart" otherwise.
     */
    public function check(string $pageContent, string $url): BookStatus
    {
        if (Str::contains($pageContent, 'Sold out', ignoreCase: true)) {
            return BookStatus::Unavailable;
        }

        if (Str::contains($pageContent, 'Add to cart', ignoreCase: true)) {
            return BookStatus::Available;
        }

        return BookStatus::Unsure;
    }
}
