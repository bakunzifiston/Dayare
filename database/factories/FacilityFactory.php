<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'facility_name' => fake()->company().' — '.fake()->word(),
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'license_number' => 'LIC-'.strtoupper(substr(uniqid(), -8)),
            'license_issue_date' => now()->subYear(),
            'license_expiry_date' => now()->addYear(),
            'status' => Facility::STATUS_ACTIVE,
        ];
    }

    public function slaughterhouse(): static
    {
        return $this->state(fn () => [
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
        ]);
    }
}
