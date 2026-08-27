<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;

/** Catch-all that returns Unsure for any URL not handled by a more specific checker. */
class DefaultChecker implements CheckerInterface
{
    /** Always true — this is the fallback, so it must be registered last. */
    public function supports(string $url): bool
    {
        return true;
    }

    /** No publisher-specific knowledge, so the result is always Unsure. */
    public function check(string $pageContent, string $url): BookStatus
    {
        return BookStatus::Unsure;
    }
}
