<?php

namespace Database\Factories;

use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlaughterExecution>
 */
class SlaughterExecutionFactory extends Factory
{
    protected $model = SlaughterExecution::class;

    public function definition(): array
    {
        return [
            'slaughter_plan_id' => SlaughterPlan::factory(),
            'actual_animals_slaughtered' => fake()->numberBetween(5, 20),
            'slaughter_time' => now(),
            'status' => SlaughterExecution::STATUS_COMPLETED,
        ];
    }

    /**
     * Creates one SlaughterExecutionItem per animal assigned to the execution's plan.
     * Meat quantity defaults to 50% of live weight when available, otherwise 25.00 kg.
     */
    public function withExecutionItems(): static
    {
        return $this->afterCreating(function (SlaughterExecution $execution): void {
            $plan = SlaughterPlan::query()
                ->with('assignedItems')
                ->find($execution->slaughter_plan_id);

            if ($plan === null || $plan->assignedItems->isEmpty()) {
                return;
            }

            foreach ($plan->assignedItems as $assignedItem) {
                $liveWeight = $assignedItem->live_weight_kg;
                $meatQuantity = ($liveWeight !== null && (float) $liveWeight > 0)
                    ? round((float) $liveWeight * 0.50, 2)
                    : 25.00;

                SlaughterExecutionItem::query()->create([
                    'slaughter_execution_id' => $execution->id,
                    'animal_intake_item_id' => $assignedItem->id,
                    'meat_quantity_kg' => $meatQuantity,
                    'notes' => null,
                ]);
            }

            $execution->update([
                'actual_animals_slaughtered' => $plan->assignedItems->count(),
                'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
            ]);
        });
    }
}
