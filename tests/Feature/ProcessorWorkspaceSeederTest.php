<?php

namespace Tests\Feature;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\DeliveryConfirmation;
use App\Models\TransportTrip;
use App\Models\User;
use Database\Seeders\AdministrativeDivisionSeeder;
use Database\Seeders\ColdRoomStandardSeeder;
use Database\Seeders\ProcessorWorkspaceSeeder;
use Database\Seeders\SpeciesSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessorWorkspaceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_ten_processor_businesses_with_full_chain(): void
    {
        $this->seed([
            AdministrativeDivisionSeeder::class,
            SpeciesSeeder::class,
            UnitSeeder::class,
            ColdRoomStandardSeeder::class,
            ProcessorWorkspaceSeeder::class,
        ]);

        $this->assertSame(10, Business::query()
            ->where('type', Business::TYPE_PROCESSOR)
            ->where('registration_number', 'like', 'PWS-RDB-%')
            ->count());

        $this->assertTrue(User::query()->where('email', 'owner.pws.1@processor.rw')->exists());
        $this->assertGreaterThan(0, AnimalIntake::query()->where('animal_health_certificate_number', 'like', 'PWS-AHC%')->orWhereNull('animal_health_certificate_number')->count());
        $this->assertGreaterThan(0, Certificate::query()->where('certificate_number', 'like', 'PWS-CERT%')->count());
        $this->assertGreaterThan(0, TransportTrip::query()->whereHas('certificate', fn ($q) => $q->where('certificate_number', 'like', 'PWS-CERT%'))->count());
        $this->assertGreaterThan(0, DeliveryConfirmation::query()->whereHas('transportTrip.certificate', fn ($q) => $q->where('certificate_number', 'like', 'PWS-CERT%'))->count());

        $this->seed(ProcessorWorkspaceSeeder::class);
        $this->assertSame(10, Business::query()->where('registration_number', 'like', 'PWS-RDB-%')->count());
    }
}
