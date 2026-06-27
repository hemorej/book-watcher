<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;

/** Catch-all that returns Unsure for any URL not handled by a more specific checker. */
class DefaultChecker implements CheckerInterface
{
    public function supports(string $url): bool
    {
        return true;
    }

    public function check(string $pageContent, string $url): BookStatus
    {
        return BookStatus::Unsure;
    }
}
