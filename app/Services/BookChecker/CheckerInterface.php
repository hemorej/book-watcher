<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;

interface CheckerInterface
{
    /** Returns true if this checker knows how to parse the given URL. */
    public function supports(string $url): bool;

    /** Parse already-fetched page HTML and return the book's availability status. */
    public function check(string $pageContent, string $url): BookStatus;
}
