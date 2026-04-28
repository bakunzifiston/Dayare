<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        $reg = 'REG-FCT-'.strtoupper(substr(uniqid(), -8));

        return [
            'user_id' => User::factory(),
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => fake()->company().' (Test)',
            'registration_number' => $reg,
            'tax_id' => 'TIN-'.str_replace('-', '', $reg),
            'contact_phone' => '+25078'.fake()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'status' => Business::STATUS_ACTIVE,
            'owner_first_name' => fake()->firstName(),
            'owner_last_name' => fake()->lastName(),
            'ownership_type' => 'sole_proprietor',
        ];
    }

    public function farmer(): static
    {
        return $this->state(fn () => [
            'type' => Business::TYPE_FARMER,
        ]);
    }

    public function logistics(): static
    {
        return $this->state(fn () => [
            'type' => Business::TYPE_LOGISTICS,
        ]);
    }
}
