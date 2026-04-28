<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => now()->subYears(35),
            'nationality' => 'Rwandan',
            'registration_number' => 'SUP-FCT-'.strtoupper(substr(uniqid(), -8)),
            'type' => 'livestock_supply',
            'phone' => '+25078'.fake()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'address_line_1' => fake()->streetAddress().', Rwanda',
            'is_active' => true,
            'supplier_status' => Supplier::STATUS_APPROVED,
        ];
    }
}
