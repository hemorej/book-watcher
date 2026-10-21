<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use App\Providers\AppServiceProvider;

/**
 * One publisher's availability-parsing strategy. Implementations are stateless
 * and registered in order in {@see AppServiceProvider}.
 */
interface CheckerInterface
{
    /** Returns true if this checker knows how to parse the given URL. */
    public function supports(string $url): bool;

    /** Parse already-fetched page HTML and return the book's availability status. */
    public function check(string $pageContent, string $url): BookStatus;
}
