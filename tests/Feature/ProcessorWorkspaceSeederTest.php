<?php

namespace Tests\Feature;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\DeliveryConfirmation;
use App\Models\RicaMonthlyInspectionReport;
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

    public function test_seeder_creates_twelve_processor_businesses_with_full_chain(): void
    {
        $this->seed([
            AdministrativeDivisionSeeder::class,
            SpeciesSeeder::class,
            UnitSeeder::class,
            ColdRoomStandardSeeder::class,
            ProcessorWorkspaceSeeder::class,
        ]);

        $this->assertSame(12, Business::query()
            ->where('type', Business::TYPE_PROCESSOR)
            ->where('registration_number', 'like', 'PWS-RDB-%')
            ->count());

        $this->assertTrue(User::query()->where('email', 'owner@saban.rw')->exists());
        $this->assertTrue(User::query()->where('email', 'owner@kabuga-abattoir.rw')->exists());
        $this->assertSame('info@saban.rw', Business::query()->where('registration_number', 'PWS-RDB-001')->value('email'));
        $this->assertSame("Societe d'Abattoir Nyabugogo (SABAN)", Business::query()->where('registration_number', 'PWS-RDB-001')->value('business_name'));
        $this->assertSame('Kabuga Pig/Modern Abattoir', Business::query()->where('registration_number', 'PWS-RDB-012')->value('business_name'));
        $this->assertGreaterThan(
            0,
            RicaMonthlyInspectionReport::query()
                ->whereHas('facility.business', fn ($q) => $q->where('registration_number', 'like', 'PWS-RDB-%'))
                ->count()
        );
        $this->assertGreaterThan(
            0,
            RicaMonthlyInspectionReport::query()
                ->where('status', RicaMonthlyInspectionReport::STATUS_SUBMITTED)
                ->whereHas('facility.business', fn ($q) => $q->where('registration_number', 'like', 'PWS-RDB-%'))
                ->count()
        );
        $this->assertGreaterThan(0, AnimalIntake::query()->where('animal_health_certificate_number', 'like', 'PWS-AHC%')->orWhereNull('animal_health_certificate_number')->count());
        $this->assertGreaterThan(0, Certificate::query()->where('certificate_number', 'like', 'PWS-CERT%')->count());
        $this->assertGreaterThan(0, TransportTrip::query()->whereHas('certificate', fn ($q) => $q->where('certificate_number', 'like', 'PWS-CERT%'))->count());
        $this->assertGreaterThan(0, DeliveryConfirmation::query()->whereHas('transportTrip.certificate', fn ($q) => $q->where('certificate_number', 'like', 'PWS-CERT%'))->count());

        $this->seed(ProcessorWorkspaceSeeder::class);
        $this->assertSame(12, Business::query()->where('registration_number', 'like', 'PWS-RDB-%')->count());
    }
}
