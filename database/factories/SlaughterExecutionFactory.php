<?php

namespace Database\Factories;

use App\Models\SlaughterExecution;
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
}
