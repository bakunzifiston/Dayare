<?php

namespace App\Enums;

enum ReceivedUnit: string
{
    case Units = 'units';
    case Kg = 'kg';
    case G = 'g';
    case Tonnes = 'tonnes';
    case Carcasses = 'carcasses';
    case Boxes = 'boxes';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Units => __('units'),
            self::Kg => __('kg'),
            self::G => __('g'),
            self::Tonnes => __('tonnes'),
            self::Carcasses => __('carcasses'),
            self::Boxes => __('boxes'),
        };
    }
}
