<?php

namespace Database\Factories;

use App\Models\AnteMortemInspectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnteMortemInspectionItem>
 */
class AnteMortemInspectionItemFactory extends Factory
{
    protected $model = AnteMortemInspectionItem::class;

    public function definition(): array
    {
        return [
            'ante_mortem_inspection_id' => null,
            'animal_intake_item_id' => null,
            'outcome' => AnteMortemInspectionItem::OUTCOME_APPROVED,
            'outcome_notes' => null,
        ];
    }
}
