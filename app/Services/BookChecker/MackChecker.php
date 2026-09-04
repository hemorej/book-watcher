<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use Illuminate\Support\Str;

/** Availability parser for MACK (mackbooks.co.uk) product pages. */
class MackChecker implements CheckerInterface
{
    public function supports(string $url): bool
    {
        return Str::contains($url, 'mackbooks');
    }

    /**
     * A price string (e.g. "£55.00 GBP") or an "Add to cart" button means the
     * book is in stock and available; anything else → Unavailable.
     */
    public function check(string $pageContent, string $url): BookStatus
    {
        if (preg_match('/£\s*[\d.,]+/', $pageContent) || Str::contains($pageContent, 'Add to cart', ignoreCase: true)) {
            return BookStatus::Available;
        }

        return BookStatus::Unavailable;
    }
}
