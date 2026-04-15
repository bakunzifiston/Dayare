<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Kilogram', 'code' => 'kg', 'sort_order' => 1],
            ['name' => 'Liter', 'code' => 'liters', 'sort_order' => 2],
            ['name' => 'Piece', 'code' => 'pieces', 'sort_order' => 3],
        ];

        foreach ($defaults as $row) {
            Unit::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'sort_order' => $row['sort_order'], 'is_active' => true]
            );
        }
    }
}
