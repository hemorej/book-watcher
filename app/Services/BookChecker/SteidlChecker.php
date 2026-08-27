<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use Illuminate\Support\Str;

/** Availability parser for Steidl (steidl.de) product pages. */
class SteidlChecker implements CheckerInterface
{
    public function supports(string $url): bool
    {
        return Str::contains($url, 'steidl');
    }

    /**
     * Reads the `.headline-left` banner: "Free shipping" → Available,
     * "Not yet published" → Unavailable, anything else → Unsure.
     */
    public function check(string $pageContent, string $url): BookStatus
    {
        // Steidl puts shipping/availability info in a `.headline-left` div.
        // /s so `.` spans newlines; non-greedy so it stops at the first </div>.
        preg_match('/<div class="headline headline\-left">(.*?)<\/div>/s', $pageContent, $matches);

        $headline = $matches[0] ?? '';

        if (Str::contains($headline, 'Free shipping')) {
            return BookStatus::Available;
        }

        if (Str::contains($headline, 'Not yet published')) {
            return BookStatus::Unavailable;
        }

        return BookStatus::Unsure;
    }
}
