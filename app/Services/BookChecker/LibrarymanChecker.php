<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use Illuminate\Support\Str;

/** Availability parser for Libraryman (libraryman.se) product pages. */
class LibrarymanChecker implements CheckerInterface
{
    public function supports(string $url): bool
    {
        return Str::contains($url, 'libraryman');
    }

    /**
     * Each edition has its own "btn-add" cart button; an "out" class marks
     * that edition "Out of print". Available if any edition lacks it.
     */
    public function check(string $pageContent, string $url): BookStatus
    {
        preg_match_all('/<span class="btn btn-add( out)?">/', $pageContent, $matches);

        $buttons = $matches[0] ?? [];

        if ($buttons === []) {
            return BookStatus::Unsure;
        }

        foreach ($buttons as $index => $button) {
            if ($matches[1][$index] === '') {
                return BookStatus::Available;
            }
        }

        return BookStatus::Unavailable;
    }
}
