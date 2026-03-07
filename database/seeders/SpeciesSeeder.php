<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Cattle', 'code' => 'cattle', 'sort_order' => 1],
            ['name' => 'Goat', 'code' => 'goat', 'sort_order' => 2],
            ['name' => 'Sheep', 'code' => 'sheep', 'sort_order' => 3],
            ['name' => 'Pig', 'code' => 'pig', 'sort_order' => 4],
            ['name' => 'Other', 'code' => 'other', 'sort_order' => 99],
        ];

        foreach ($defaults as $row) {
            Species::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'sort_order' => $row['sort_order'], 'is_active' => true]
            );
        }
    }
}

