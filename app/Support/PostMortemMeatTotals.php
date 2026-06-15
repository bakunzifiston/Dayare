<?php

namespace App\Support;

use Illuminate\Support\Collection;

class PostMortemMeatTotals
{
    /**
     * @param  iterable<int, array<string, mixed>>  $itemOutcomes
     * @param  Collection<int, array<string, mixed>>  $animalsById  keyed by animal_intake_item_id
     * @return array{total_examined: float, approved_quantity: float, condemned_quantity: float}
     */
    public static function fromItemOutcomes(iterable $itemOutcomes, Collection $animalsById): array
    {
        $examinedKg = 0.0;
        $approvedKg = 0.0;
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
                $approvedKg += $afterKg > 0 ? $afterKg : $beforeKg;
            } elseif ($result === 'condemned') {
                $condemnedKg += $beforeKg;
            }
        }

        return [
            'total_examined' => round($examinedKg, 2),
            'approved_quantity' => round($approvedKg, 2),
            'condemned_quantity' => round($condemnedKg, 2),
        ];
    }
}
