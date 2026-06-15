<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Support\PostMortemMeatTotals;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Batch>
 */
class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        return [
            'slaughter_execution_id' => SlaughterExecution::factory(),
            'inspector_id' => null,
            'species' => Batch::SPECIES_CATTLE,
            'quantity' => 0.00,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => Batch::STATUS_PENDING,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Batch $batch): void {
            if ($batch->inspector_id !== null) {
                return;
            }

            $execution = $batch->slaughterExecution
                ?? ($batch->slaughter_execution_id
                    ? SlaughterExecution::with('slaughterPlan')->find($batch->slaughter_execution_id)
                    : null);

            $facilityId = $execution?->slaughterPlan?->facility_id;
            if ($facilityId === null) {
                return;
            }

            $batch->inspector_id = Inspector::factory()->create([
                'facility_id' => $facilityId,
            ])->id;
        });
    }

    /**
     * Creates one BatchItem per slaughter execution item on the linked execution.
     */
    public function withItems(): static
    {
        return $this->afterCreating(function (Batch $batch): void {
            $batch->loadMissing('slaughterExecution.executionItems');

            foreach ($batch->slaughterExecution->executionItems as $execItem) {
                $batch->items()->create([
                    'slaughter_execution_item_id' => $execItem->id,
                    'animal_intake_item_id' => $execItem->animal_intake_item_id,
                    'meat_quantity_kg' => $execItem->meat_quantity_kg,
                    'notes' => null,
                ]);
            }
        });
    }

    /**
     * Creates batch items (when missing) and a post-mortem inspection with per-animal outcomes.
     */
    public function withPostMortem(): static
    {
        return $this->withItems()->afterCreating(function (Batch $batch): void {
            if ($batch->postMortemInspection()->exists()) {
                return;
            }

            $batch->load('items');

            $itemOutcomes = $batch->items->map(fn ($batchItem) => [
                'batch_item_id' => $batchItem->id,
                'animal_intake_item_id' => $batchItem->animal_intake_item_id,
                'outcome' => PostMortemInspectionItem::OUTCOME_APPROVED,
                'carcass_weight_kg' => round((float) $batchItem->meat_quantity_kg * 0.88, 2),
            ])->all();

            $animalsById = $batch->inspectableAnimalsForPostMortem()->keyBy('animal_intake_item_id');
            $meatTotals = PostMortemMeatTotals::fromItemOutcomes($itemOutcomes, $animalsById);

            $pm = PostMortemInspection::create([
                'batch_id' => $batch->id,
                'inspector_id' => $batch->inspector_id,
                'species' => $batch->species,
                'total_examined' => $meatTotals['total_examined'],
                'approved_quantity' => $meatTotals['approved_quantity'],
                'condemned_quantity' => $meatTotals['condemned_quantity'],
                'inspection_date' => today(),
                'result' => PostMortemInspection::RESULT_APPROVED,
            ]);

            foreach ($batch->items as $batchItem) {
                $pm->inspectionItems()->create([
                    'batch_item_id' => $batchItem->id,
                    'animal_intake_item_id' => $batchItem->animal_intake_item_id,
                    'outcome' => PostMortemInspectionItem::OUTCOME_APPROVED,
                    'carcass_weight_kg' => round((float) $batchItem->meat_quantity_kg * 0.88, 2),
                    'outcome_notes' => null,
                ]);
            }
        });
    }
}
