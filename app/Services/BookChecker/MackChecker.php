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
     * MACK marks unreleased titles "Available to pre-order"; once that phrase
     * is gone the book ships, so its absence is treated as Available.
     */
    public function check(string $pageContent, string $url): BookStatus
    {
        // Mack shows "Available to pre-order" while a book is unreleased; absence means it ships now
        if (Str::contains($pageContent, 'Available to pre-order')) {
            return BookStatus::Unavailable;
        }

        return BookStatus::Available;
    }
}
