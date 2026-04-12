<?php

namespace App\Services\Farmer;

use App\Models\Livestock;

/**
 * Computes A / B / C from health_status, feeding_type, and breed (no DB writes).
 */
final class LivestockQualityScore
{
    public const TIER_A = 'A';

    public const TIER_B = 'B';

    public const TIER_C = 'C';

    /**
     * @return array{tier: string, points: int, max: int, breakdown: array<string, int>}
     */
    public static function evaluate(Livestock $livestock): array
    {
        $health = self::healthPoints($livestock->health_status);
        $feeding = self::feedingPoints($livestock->feeding_type);
        $breed = self::breedPoints($livestock->breed);

        $points = $health + $feeding + $breed;
        $max = 9;

        $tier = $points >= 7 ? self::TIER_A : ($points >= 4 ? self::TIER_B : self::TIER_C);

        return [
            'tier' => $tier,
            'points' => $points,
            'max' => $max,
            'breakdown' => [
                'health' => $health,
                'feeding' => $feeding,
                'breed' => $breed,
            ],
        ];
    }

    private static function healthPoints(?string $status): int
    {
        return match ($status) {
            Livestock::HEALTH_EXCELLENT => 3,
            Livestock::HEALTH_GOOD => 2,
            Livestock::HEALTH_FAIR => 1,
            Livestock::HEALTH_POOR => 0,
            default => 1,
        };
    }

    private static function feedingPoints(?string $feeding): int
    {
        return match ($feeding) {
            Livestock::FEEDING_ORGANIC, Livestock::FEEDING_PASTURE => 3,
            Livestock::FEEDING_MIXED => 2,
            Livestock::FEEDING_GRAIN, Livestock::FEEDING_OTHER => 1,
            default => 1,
        };
    }

    private static function breedPoints(?string $breed): int
    {
        $b = trim((string) $breed);
        if ($b === '') {
            return 1;
        }

        $heritageHints = ['angus', 'hereford', 'holstein', 'jersey', 'boer', 'sasso', 'broiler', 'layer', 'heritage', 'local'];
        $lower = strtolower($b);
        foreach ($heritageHints as $hint) {
            if (str_contains($lower, $hint)) {
                return 3;
            }
        }

        return 2;
    }
}
