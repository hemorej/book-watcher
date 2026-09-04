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
     * Reads the `.headline-left` banner: "Free shipping" or a price (e.g. "€ 280.00")
     * → Available; "out of print"/"unavailable"/"not yet published"/"pre-order" → Unavailable;
     * anything else → Unsure.
     */
    public function check(string $pageContent, string $url): BookStatus
    {
        // Steidl puts shipping/availability info in a `.headline-left` div.
        // /s so `.` spans newlines; non-greedy so it stops at the first </div>.
        preg_match('/<div class="headline headline\-left">(.*?)<\/div>/s', $pageContent, $matches);

        $headline = $matches[0] ?? '';

        if (Str::contains($headline, 'Free shipping') || preg_match('/€\s*[\d.,]+/', $headline)) {
            return BookStatus::Available;
        }

        if (Str::contains($headline, ['out of print', 'unavailable', 'not yet published', 'pre-order'], ignoreCase: true)) {
            return BookStatus::Unavailable;
        }

        return BookStatus::Unsure;
    }
}
