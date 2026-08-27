<?php

namespace App\Enums;

/**
 * Availability of a watched book.
 *
 * `Unsure` is the checkers' "couldn't tell" result — an unreachable page, an
 * unrecognised publisher, or page markup that matched none of the known
 * patterns. It is deliberately distinct from `Unavailable` so a scraping
 * breakage never looks like a definitive "not for sale".
 */
enum BookStatus: string
{
    case Available = 'available';
    case Unavailable = 'unavailable';
    case Unsure = 'unsure';

    /** Human-readable label for the status badge. */
    public function label(): string
    {
        return match($this) {
            self::Available   => 'Available',
            self::Unavailable => 'Not Available',
            self::Unsure      => 'Unsure',
        };
    }

    /** Returns a Flux UI color name used by the status badge component. */
    public function color(): string
    {
        return match($this) {
            self::Available   => 'green',
            self::Unavailable => 'amber',
            self::Unsure      => 'zinc',
        };
    }
}
