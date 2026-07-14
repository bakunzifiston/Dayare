<?php

namespace Tests\Feature;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\User;
use Database\Seeders\AdministrativeDivisionSeeder;
use Database\Seeders\ColdRoomStandardSeeder;
use Database\Seeders\RwandaMeatProcessorDemoSeeder;
use Database\Seeders\SpeciesSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RwandaMeatProcessorDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_rwanda_meat_processor_workspace(): void
    {
        $this->seed([
            AdministrativeDivisionSeeder::class,
            SpeciesSeeder::class,
            UnitSeeder::class,
            ColdRoomStandardSeeder::class,
            RwandaMeatProcessorDemoSeeder::class,
        ]);

        $business = Business::query()
            ->where('registration_number', RwandaMeatProcessorDemoSeeder::REGISTRATION_NUMBER)
            ->first();

        $this->assertNotNull($business);
        $this->assertSame('Nyagatare Prime Meats Ltd', $business->business_name);
        $this->assertTrue(User::query()->where('email', 'jeanpierre.mukamana@nyagataprime.rw')->exists());
        $this->assertGreaterThan(150, AnimalIntake::query()->whereHas('facility', fn ($q) => $q->where('business_id', $business->id))->count());
        $this->assertGreaterThan(0, AnimalIntakeItem::query()->where('service_fee', '>', 0)->count());
        $this->assertGreaterThan(0, Certificate::query()->where('certificate_number', 'like', 'CERT-NPM%')->count());
        $this->assertGreaterThan(0, RicaMonthlyInspectionReport::query()
            ->where('status', RicaMonthlyInspectionReport::STATUS_SUBMITTED)
            ->count());

        $pmInspection = PostMortemInspection::query()
            ->whereHas('batch.slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $business->id))
            ->first();
        $this->assertNotNull($pmInspection);
        $this->assertGreaterThan(10, (float) $pmInspection->total_examined, 'Total examined meat should be stored in kg');
        $this->assertGreaterThan(0, (float) $pmInspection->approved_quantity);

        $condemnedItem = PostMortemInspectionItem::query()
            ->where('outcome', PostMortemInspectionItem::OUTCOME_CONDEMNED)
            ->whereHas('inspection.batch.slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $business->id))
            ->first();
        if ($condemnedItem) {
            $this->assertNotNull($condemnedItem->condemned_weight_kg);
            $this->assertGreaterThan(0, (float) $condemnedItem->condemned_weight_kg);
            $this->assertSame('Liver', $condemnedItem->seized_part);
        }

        $this->seed(RwandaMeatProcessorDemoSeeder::class);
        $this->assertSame(1, Business::query()
            ->where('registration_number', RwandaMeatProcessorDemoSeeder::REGISTRATION_NUMBER)
            ->count());
    }
}
