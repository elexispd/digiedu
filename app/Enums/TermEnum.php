<?php

namespace App\Enums;

enum TermEnum: int
{
    case FIRST = 1;
    case SECOND = 2;
    case THIRD = 3;

    /**
     * Get all the values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
