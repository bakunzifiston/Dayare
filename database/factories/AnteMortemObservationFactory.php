<?php

namespace Database\Factories;

use App\Models\AnteMortemObservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnteMortemObservation>
 */
class AnteMortemObservationFactory extends Factory
{
    protected $model = AnteMortemObservation::class;

    public function definition(): array
    {
        return [
            'ante_mortem_inspection_id' => null,
            'item' => 'locomotion',
            'value' => 'normal',
            'notes' => null,
        ];
    }
}
