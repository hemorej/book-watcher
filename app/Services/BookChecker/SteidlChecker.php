<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use Illuminate\Support\Str;

class SteidlChecker implements CheckerInterface
{
    public function supports(string $url): bool
    {
        return Str::contains($url, 'steidl');
    }

    public function check(string $pageContent, string $url): BookStatus
    {
        // Steidl puts shipping/availability info in a `.headline-left` div
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
