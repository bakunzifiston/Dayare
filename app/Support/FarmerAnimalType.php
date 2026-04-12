<?php

namespace App\Support;

use App\Models\AnimalIntake;

/**
 * Canonical animal types for farmer livestock & supply requests (lowercase).
 */
class FarmerAnimalType
{
    public const CATTLE = 'cattle';

    public const GOAT = 'goat';

    public const PIG = 'pig';

    public const POULTRY = 'poultry';

    /** @var list<string> */
    public const ALL = [
        self::CATTLE,
        self::GOAT,
        self::PIG,
        self::POULTRY,
    ];

    public static function toIntakeSpecies(string $animalType): string
    {
        return match ($animalType) {
            self::CATTLE => AnimalIntake::SPECIES_CATTLE,
            self::GOAT => AnimalIntake::SPECIES_GOAT,
            self::PIG => AnimalIntake::SPECIES_PIG,
            self::POULTRY => AnimalIntake::SPECIES_OTHER,
            default => AnimalIntake::SPECIES_OTHER,
        };
    }
}
