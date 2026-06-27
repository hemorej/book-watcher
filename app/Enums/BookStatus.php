<?php

namespace App\Enums;

enum BookStatus: string
{
    case Available = 'available';
    case Unavailable = 'unavailable';
    case Unsure = 'unsure';

    public function label(): string
    {
        return match($this) {
            self::Available   => 'Available',
            self::Unavailable => 'Not Available',
            self::Unsure      => 'Unsure',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Available   => 'green',
            self::Unavailable => 'amber',
            self::Unsure      => 'zinc',
        };
    }
}
