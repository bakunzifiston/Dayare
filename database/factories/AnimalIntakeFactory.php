<?php

namespace Database\Factories;

use App\Models\AnimalIntake;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnimalIntake>
 */
class AnimalIntakeFactory extends Factory
{
    protected $model = AnimalIntake::class;

    public function definition(): array
    {
        $d = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'facility_id' => Facility::factory(),
            'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
            'intake_date' => $d,
            'supplier_firstname' => fake()->firstName(),
            'supplier_lastname' => fake()->lastName(),
            'farm_name' => 'Demo farm — '.fake()->word(),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => fake()->numberBetween(8, 40),
            'status' => AnimalIntake::STATUS_RECEIVED,
        ];
    }
}
