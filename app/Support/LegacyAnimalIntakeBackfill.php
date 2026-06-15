<?php

namespace App\Support;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use Illuminate\Support\Facades\DB;

class LegacyAnimalIntakeBackfill
{
    /**
     * @return array{intakes_processed: int, items_created: int}
     */
    public function run(): array
    {
        $intakesProcessed = 0;
        $itemsCreated = 0;

        AnimalIntake::query()
            ->whereDoesntHave('items')
            ->orderBy('id')
            ->chunkById(100, function ($intakes) use (&$intakesProcessed, &$itemsCreated): void {
                foreach ($intakes as $intake) {
                    $created = $this->backfillIntake($intake);
                    if ($created > 0) {
                        $intakesProcessed++;
                        $itemsCreated += $created;
                    }
                }
            });

        return [
            'intakes_processed' => $intakesProcessed,
            'items_created' => $itemsCreated,
        ];
    }

    private function backfillIntake(AnimalIntake $intake): int
    {
        $headCount = (int) ($intake->getRawOriginal('number_of_animals') ?? 0);
        if ($headCount <= 0) {
            return 0;
        }

        $species = (string) ($intake->getRawOriginal('species') ?? AnimalIntake::SPECIES_OTHER);
        $unitPrice = $this->resolveUnitPrice($intake, $headCount);
        $sex = $this->resolveSex($intake);
        $healthStatus = $intake->status === AnimalIntake::STATUS_REJECTED
            ? AnimalIntakeItem::HEALTH_REJECTED
            : AnimalIntakeItem::HEALTH_HEALTHY;

        $headerUpdates = [];
        if (empty($intake->reference)) {
            $year = $intake->created_at?->year ?? $intake->intake_date?->year ?? now()->year;
            $headerUpdates['reference'] = sprintf('INT-%d-%05d', $year, $intake->id);
        }
        if ($intake->submitted_at === null && ! $intake->is_draft) {
            $headerUpdates['submitted_at'] = $intake->intake_date?->copy()->startOfDay()
                ?? $intake->created_at
                ?? now();
        }
        if ($headerUpdates !== []) {
            $intake->update($headerUpdates);
        }

        $rows = [];
        $now = now();
        for ($n = 1; $n <= $headCount; $n++) {
            $rows[] = [
                'animal_intake_id' => $intake->id,
                'ear_tag' => sprintf('LEGACY-%d-%d', $intake->id, $n),
                'species' => $species,
                'sex' => $sex,
                'age_months' => $this->resolveAgeMonths($intake),
                'live_weight_kg' => null,
                'body_condition_score' => 'good',
                'unit_price' => $unitPrice,
                'health_status' => $healthStatus,
                'notes' => __('Migrated from legacy group intake'),
                'slaughter_plan_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('animal_intake_items')->insert($rows);

        $totalPrice = round($unitPrice * $headCount, 2);
        $intake->update([
            'number_of_animals' => $headCount,
            'total_price' => $totalPrice,
            'species' => $species,
            'unit_price' => $unitPrice,
        ]);

        return $headCount;
    }

    private function resolveUnitPrice(AnimalIntake $intake, int $headCount): float
    {
        $unitPrice = (float) ($intake->getRawOriginal('unit_price') ?? 0);
        if ($unitPrice > 0) {
            return round($unitPrice, 2);
        }

        $totalPrice = (float) ($intake->getRawOriginal('total_price') ?? 0);
        if ($totalPrice > 0 && $headCount > 0) {
            return round($totalPrice / $headCount, 2);
        }

        return 0.0;
    }

    private function resolveSex(AnimalIntake $intake): string
    {
        $sex = (string) ($intake->getRawOriginal('sex') ?? '');

        return in_array($sex, [AnimalIntake::SEX_MALE, AnimalIntake::SEX_FEMALE], true)
            ? $sex
            : AnimalIntake::SEX_MALE;
    }

    private function resolveAgeMonths(AnimalIntake $intake): ?int
    {
        $age = $intake->getRawOriginal('age');

        return $age !== null ? (int) $age : null;
    }
}
