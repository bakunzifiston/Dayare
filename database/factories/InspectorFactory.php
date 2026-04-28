<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\Inspector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inspector>
 */
class InspectorFactory extends Factory
{
    protected $model = Inspector::class;

    public function definition(): array
    {
        $suffix = strtoupper(substr(uniqid('', true), -8));

        return [
            'facility_id' => Facility::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'national_id' => 'NI-FCT-'.$suffix,
            'phone_number' => '+25078'.fake()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'dob' => fake()->dateTimeBetween('-55 years', '-25 years'),
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Test',
            'authorization_number' => 'AUTH-FCT-'.$suffix,
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle, Goat, Sheep',
            'daily_capacity' => 100,
            'status' => Inspector::STATUS_ACTIVE,
        ];
    }
}
