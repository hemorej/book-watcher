<?php

namespace App\Support;

use App\Models\Book;

/**
 * Fuzzy title matching used to line up a watched {@see Book}
 * against a candidate result from a secondary-source search.
 */
class TitleMatcher
{
    /** Percentage (0-100) similarity between two titles, ignoring case, punctuation, and whitespace differences. */
    public static function score(string $a, string $b): float
    {
        similar_text(self::normalize($a), self::normalize($b), $percent);

        return $percent;
    }

    private static function normalize(string $title): string
    {
        $title = mb_strtolower($title);
        $title = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $title) ?? $title;
        $title = preg_replace('/\s+/', ' ', $title) ?? $title;

        return trim($title);
    }
}
