<?php

namespace App\Support;

use App\Models\LibraryBook;

/**
 * ISBN normalisation helpers.
 *
 * Every ISBN entering the library is normalised to a 13-digit string so the
 * {@see LibraryBook} `isbn` column is a stable dedupe key regardless
 * of whether the source list used ISBN-10, ISBN-13, hyphens, or spaces.
 */
class Isbn
{
    /**
     * Strip separators, validate the check digit, and return a 13-digit ISBN.
     *
     * Accepts ISBN-10 (converted to the 978- prefixed ISBN-13) or ISBN-13.
     * Returns null when the input is not 10/13 characters or the check digit
     * does not match — both usually mean a typo in the source list.
     */
    public static function normalize(string $raw): ?string
    {
        $value = strtoupper(preg_replace('/[^0-9Xx]/', '', $raw) ?? '');

        return match (strlen($value)) {
            10 => self::isValidIsbn10($value) ? self::toIsbn13($value) : null,
            13 => self::isValidIsbn13($value) ? $value : null,
            default => null,
        };
    }

    private static function isValidIsbn10(string $isbn): bool
    {
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $char = $isbn[$i];
            $digit = ($char === 'X') ? 10 : (ctype_digit($char) ? (int) $char : -1);
            if ($digit < 0 || ($char === 'X' && $i !== 9)) {
                return false;
            }
            $sum += $digit * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    private static function isValidIsbn13(string $isbn): bool
    {
        if (! ctype_digit($isbn)) {
            return false;
        }

        return self::isbn13CheckDigit(substr($isbn, 0, 12)) === (int) $isbn[12];
    }

    private static function toIsbn13(string $isbn10): string
    {
        $body = '978'.substr($isbn10, 0, 9);

        return $body.self::isbn13CheckDigit($body);
    }

    private static function isbn13CheckDigit(string $twelveDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $twelveDigits[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10;
    }
}
