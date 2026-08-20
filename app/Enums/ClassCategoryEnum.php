<?php

namespace App\Enums;

enum ClassCategoryEnum: string
{
    case NURSERY_PRE_NURSERY = 'Nursery/Pre-Nursery';
    case PRIMARY = 'Primary';
    case JUNIOR_SECONDARY = 'Junior Secondary';
    case SENIOR_SECONDARY = 'Senior Secondary';

    /**
     * Get all the values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
