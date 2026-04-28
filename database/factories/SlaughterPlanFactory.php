<?php

namespace Database\Factories;

use App\Models\AnimalIntake;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlaughterPlan>
 *
 * Note: Intake and inspector may reference different facilities; override in tests when co-location matters.
 */
class SlaughterPlanFactory extends Factory
{
    protected $model = SlaughterPlan::class;

    public function definition(): array
    {
        return [
            'slaughter_date' => now()->toDateString(),
            'facility_id' => Facility::factory(),
            'animal_intake_id' => AnimalIntake::factory(),
            'inspector_id' => Inspector::factory(),
            'species' => SlaughterPlan::SPECIES_CATTLE,
            'number_of_animals_scheduled' => fake()->numberBetween(5, 25),
            'status' => SlaughterPlan::STATUS_APPROVED,
        ];
    }
}
