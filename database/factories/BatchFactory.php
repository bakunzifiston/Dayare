<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\SlaughterExecution;
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
            'species' => Batch::SPECIES_CATTLE,
            'quantity' => fake()->numberBetween(5, 30),
            'quantity_unit' => 'kg',
            'status' => Batch::STATUS_PENDING,
        ];
    }
}
