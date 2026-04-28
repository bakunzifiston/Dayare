<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Demand;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Demand>
 */
class DemandFactory extends Factory
{
    protected $model = Demand::class;

    public function definition(): array
    {
        $y = (int) date('Y');
        $num = fake()->unique()->numberBetween(1, 99999);

        return [
            'business_id' => Business::factory(),
            'demand_number' => "DEM-FCT-{$y}-".str_pad((string) $num, 5, '0', STR_PAD_LEFT),
            'title' => 'Factory demand '.$num,
            'destination_facility_id' => Facility::factory(),
            'species' => 'Cattle',
            'product_description' => 'Fresh meat',
            'quantity' => (string) fake()->numberBetween(20, 500),
            'quantity_unit' => 'kg',
            'requested_delivery_date' => now()->addWeek(),
            'status' => Demand::STATUS_DRAFT,
        ];
    }
}
