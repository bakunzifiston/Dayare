<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\SalesComplianceSite;
use App\Support\SalesComplianceCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesComplianceSite>
 */
class SalesComplianceSiteFactory extends Factory
{
    protected $model = SalesComplianceSite::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'site_type' => SalesComplianceCatalog::SITE_RESTAURANT,
            'name' => fake()->company().' Kitchen',
            'location_address' => fake()->streetAddress().', Kigali',
            'contact_name' => fake()->name(),
            'contact_phone' => '+25078'.fake()->numerify('######'),
            'contact_email' => fake()->safeEmail(),
            'is_active' => true,
        ];
    }

    public function butchery(): static
    {
        return $this->state(fn () => [
            'site_type' => SalesComplianceCatalog::SITE_BUTCHERY,
            'name' => fake()->company().' Butchery',
        ]);
    }

    public function privateEvent(): static
    {
        return $this->state(fn () => [
            'site_type' => SalesComplianceCatalog::SITE_PRIVATE_EVENT,
            'event_type' => 'wedding',
            'event_name' => fake()->lastName().' wedding',
            'contact_name' => null,
            'contact_phone' => null,
            'contact_email' => null,
        ]);
    }

    public function bar(): static
    {
        return $this->state(fn () => [
            'site_type' => SalesComplianceCatalog::SITE_BAR,
            'name' => fake()->company().' Bar',
        ]);
    }
}
