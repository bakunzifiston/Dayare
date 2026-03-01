<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(AdministrativeDivisionSeeder::class);
        $this->call(TestDataSeeder::class);
        $this->call(SlaughterPlanSeeder::class);
        $this->call(SlaughterExecutionSeeder::class);
        $this->call(AnteMortemInspectionSeeder::class);
        $this->call(BatchSeeder::class);
        $this->call(PostMortemInspectionSeeder::class);
        $this->call(CertificateSeeder::class);
        $this->call(TransportTripSeeder::class);
        $this->call(DeliveryConfirmationSeeder::class);
    }
}
