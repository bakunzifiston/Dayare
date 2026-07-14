<?php

namespace App\Support;

use Illuminate\Support\Collection;

class PostMortemMeatTotals
{
    /**
     * @param  iterable<int, array<string, mixed>>  $itemOutcomes
     * @param  Collection<int, array<string, mixed>>  $animalsById  keyed by animal_intake_item_id
     * @return array{
     *     total_examined: float,
     *     approved_quantity: float,
     *     approved_carcass_kg: float,
     *     approved_other_meat_kg: float,
     *     condemned_quantity: float
     * }
     */
    public static function fromItemOutcomes(iterable $itemOutcomes, Collection $animalsById): array
    {
        $examinedKg = 0.0;
        $carcassApprovedKg = 0.0;
        $otherApprovedKg = 0.0;
        $condemnedKg = 0.0;

        foreach ($itemOutcomes as $outcome) {
            if (! is_array($outcome)) {
                continue;
            }

            $animalId = (int) ($outcome['animal_intake_item_id'] ?? 0);
            $result = (string) ($outcome['outcome'] ?? '');

            if ($animalId === 0 || ! in_array($result, ['approved', 'condemned', 'deferred'], true)) {
                continue;
            }

            $animal = $animalsById->get($animalId, []);
            $beforeKg = (float) ($animal['meat_quantity_kg'] ?? 0);
            $afterKg = (float) ($outcome['carcass_weight_kg'] ?? 0);

            $examinedKg += $beforeKg;

            if ($result === 'approved') {
                $carcassPart = $afterKg > 0 ? $afterKg : $beforeKg;
                $carcassApprovedKg += $carcassPart;
                if ($afterKg > 0 && $beforeKg > $afterKg) {
                    $otherApprovedKg += $beforeKg - $afterKg;
                }
            } elseif ($result === 'condemned') {
                $condemnedPartKg = (float) ($outcome['condemned_weight_kg'] ?? 0);
                $condemnedKg += $condemnedPartKg > 0 ? $condemnedPartKg : $beforeKg;
            }
        }

        $approvedKg = $carcassApprovedKg + $otherApprovedKg;

        return [
            'total_examined' => round($examinedKg, 2),
            'approved_quantity' => round($approvedKg, 2),
            'approved_carcass_kg' => round($carcassApprovedKg, 2),
            'approved_other_meat_kg' => round($otherApprovedKg, 2),
            'condemned_quantity' => round($condemnedKg, 2),
        ];
    }
}
