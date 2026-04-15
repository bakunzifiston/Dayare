<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. All test data is Rwanda-related (locations, names, plates, etc.).
     * Run: php artisan db:seed  or  php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call(AdministrativeDivisionSeeder::class);
        $this->call(SpeciesSeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(TestDataSeeder::class);
        $this->call(AnimalIntakeSeeder::class);
        $this->call(SlaughterPlanSeeder::class);
        $this->call(SlaughterExecutionSeeder::class);
        $this->call(AnteMortemInspectionSeeder::class);
        $this->call(BatchSeeder::class);
        $this->call(PostMortemInspectionSeeder::class);
        $this->call(CertificateSeeder::class);
        $this->call(WarehouseStorageSeeder::class);
        $this->call(TemperatureLogSeeder::class);
        $this->call(TransportTripSeeder::class);
        $this->call(DeliveryConfirmationSeeder::class);
        $this->call(DemandSeeder::class);
        $this->call(ClientActivitySeeder::class);
    }
}
