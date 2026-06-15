<?php

namespace Database\Factories;

use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\AnteMortemObservation;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use App\Support\AnteMortemChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnteMortemInspection>
 */
class AnteMortemInspectionFactory extends Factory
{
    protected $model = AnteMortemInspection::class;

    public function definition(): array
    {
        return [
            'slaughter_plan_id' => SlaughterPlan::factory(),
            'inspector_id' => Inspector::factory(),
            'species' => 'Cattle',
            'number_examined' => 3,
            'number_approved' => 3,
            'number_rejected' => 0,
            'notes' => null,
            'inspection_date' => today(),
            'examined_count_source' => AnteMortemInspection::SOURCE_MANUAL,
            'notes_for_under_observation' => null,
        ];
    }

    /**
     * Creates one observation row per configured checklist item for the inspection species.
     */
    public function withObservations(): static
    {
        return $this->afterCreating(function (AnteMortemInspection $inspection): void {
            $species = (string) ($inspection->species ?? 'Cattle');
            $items = AnteMortemChecklist::itemsForSpecies($species);

            foreach ($items as $itemKey => $meta) {
                $allowed = AnteMortemChecklist::allowedValuesForItem($species, (string) $itemKey);
                $value = in_array('normal', $allowed, true)
                    ? 'normal'
                    : (in_array('approved', $allowed, true) ? 'approved' : '');

                AnteMortemObservation::factory()->create([
                    'ante_mortem_inspection_id' => $inspection->id,
                    'item' => (string) $itemKey,
                    'value' => $value,
                    'notes' => null,
                ]);
            }
        });
    }

    /**
     * Creates one approved inspection item per animal assigned to the slaughter plan.
     */
    public function withPerAnimalOutcomes(): static
    {
        return $this->afterCreating(function (AnteMortemInspection $inspection): void {
            $plan = SlaughterPlan::query()
                ->with('assignedItems')
                ->find($inspection->slaughter_plan_id);

            if ($plan === null || $plan->assignedItems->isEmpty()) {
                return;
            }

            foreach ($plan->assignedItems as $assignedItem) {
                AnteMortemInspectionItem::factory()->create([
                    'ante_mortem_inspection_id' => $inspection->id,
                    'animal_intake_item_id' => $assignedItem->id,
                    'outcome' => AnteMortemInspectionItem::OUTCOME_APPROVED,
                ]);
            }
        });
    }
}
