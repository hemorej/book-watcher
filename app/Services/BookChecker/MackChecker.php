<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use Illuminate\Support\Str;

class MackChecker implements CheckerInterface
{
    public function supports(string $url): bool
    {
        return Str::contains($url, 'mackbooks');
    }

    public function check(string $pageContent, string $url): BookStatus
    {
        // Mack shows "Available to pre-order" while a book is unreleased; absence means it ships now
        if (Str::contains($pageContent, 'Available to pre-order')) {
            return BookStatus::Unavailable;
        }

        return BookStatus::Available;
    }
}
