<?php

namespace App\Support;

class PostMortemCondemnedOrgans
{
    /**
     * Normalize submitted organ rows (and legacy single-field fallback).
     *
     * @param  array<string, mixed>  $outcome
     * @return list<array{organ_name: string, weight_kg: float}>
     */
    public static function normalizeFromOutcome(array $outcome): array
    {
        $rows = [];

        if (is_array($outcome['condemned_organs'] ?? null)) {
            foreach ($outcome['condemned_organs'] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $name = trim((string) ($row['organ_name'] ?? ''));
                $weightRaw = $row['weight_kg'] ?? null;
                $weight = ($weightRaw !== null && $weightRaw !== '' && is_numeric($weightRaw))
                    ? round((float) $weightRaw, 2)
                    : 0.0;

                if ($name === '' && $weight <= 0) {
                    continue;
                }

                $rows[] = [
                    'organ_name' => $name,
                    'weight_kg' => $weight,
                ];
            }
        }

        if ($rows === []) {
            $legacyName = trim((string) ($outcome['seized_part'] ?? ''));
            $legacyWeightRaw = $outcome['condemned_weight_kg'] ?? null;
            $legacyWeight = ($legacyWeightRaw !== null && $legacyWeightRaw !== '' && is_numeric($legacyWeightRaw))
                ? round((float) $legacyWeightRaw, 2)
                : 0.0;

            if ($legacyName !== '' || $legacyWeight > 0) {
                $rows[] = [
                    'organ_name' => $legacyName,
                    'weight_kg' => $legacyWeight,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{organ_name: string, weight_kg: float}>  $organs
     */
    public static function totalWeightKg(array $organs): float
    {
        return round(collect($organs)->sum(fn (array $row) => (float) ($row['weight_kg'] ?? 0)), 2);
    }

    /**
     * @param  list<array{organ_name: string, weight_kg: float}>  $organs
     */
    public static function seizedPartSummary(array $organs): ?string
    {
        $names = collect($organs)
            ->map(fn (array $row) => trim((string) ($row['organ_name'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return null;
        }

        return $names->implode(', ');
    }

    /**
     * @param  list<array{organ_name: string, weight_kg: float}>  $organs
     */
    public static function hasCompleteRows(array $organs): bool
    {
        if ($organs === []) {
            return false;
        }

        foreach ($organs as $row) {
            if (trim((string) ($row['organ_name'] ?? '')) === '' || (float) ($row['weight_kg'] ?? 0) <= 0) {
                return false;
            }
        }

        return true;
    }
}
