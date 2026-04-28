<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->company().' Butchery',
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+25078'.fake()->numerify('######'),
            'country' => 'Rwanda',
            'business_type' => Client::BUSINESS_TYPE_BUTCHERY,
            'address_line_1' => fake()->city().', Rwanda',
            'preferred_species' => 'Cattle',
            'is_active' => true,
        ];
    }
}
