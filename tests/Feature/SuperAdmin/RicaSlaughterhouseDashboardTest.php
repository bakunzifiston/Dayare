<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\User;
use App\Support\TenantEnvironmentScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RicaSlaughterhouseDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Facility $slaughterFacility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_RICA],
        ]);

        $business = Business::factory()->create(['user_id' => User::factory()->create()->id]);
        $district = AdministrativeDivision::create(['name' => 'Nyagatare', 'type' => AdministrativeDivision::TYPE_DISTRICT]);
        $sector = AdministrativeDivision::create(['parent_id' => $district->id, 'name' => 'Rwimiyaga', 'type' => AdministrativeDivision::TYPE_SECTOR]);
        $cell = AdministrativeDivision::create(['parent_id' => $sector->id, 'name' => 'Nyagatare Cell', 'type' => AdministrativeDivision::TYPE_CELL]);

        $this->slaughterFacility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Nyagatare Modern Slaughter House',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'district_id' => $district->id,
            'sector_id' => $sector->id,
            'cell_id' => $cell->id,
            'license_number' => 'LIC-001',
            'license_issue_date' => now()->subYear(),
            'status' => Facility::STATUS_ACTIVE,
        ]);
    }

    protected function tearDown(): void
    {
        TenantEnvironmentScope::resetFilter();
        parent::tearDown();
    }

    public function test_slaughterhouse_dashboard_defaults_to_year_to_date_and_shows_kpis(): void
    {
        $inspector = Inspector::create([
            'facility_id' => $this->slaughterFacility->id,
            'first_name' => 'Alice',
            'last_name' => 'Vet',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788123456',
            'email' => 'vet-dashboard@test.com',
            'dob' => '1985-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'authorization_number' => 'AUTH-001',
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => Inspector::STATUS_ACTIVE,
        ]);

        $slaughterTime = now()->startOfYear()->addMonths(2)->setTime(8, 0);

        $intake = AnimalIntake::create([
            'facility_id' => $this->slaughterFacility->id,
            'intake_date' => $slaughterTime,
            'supplier_firstname' => 'Jean',
            'supplier_lastname' => 'Supplier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 2,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'district_id' => $this->slaughterFacility->district_id,
            'sector_id' => $this->slaughterFacility->sector_id,
            'cell_id' => $this->slaughterFacility->cell_id,
        ]);

        $intakeItem = AnimalIntakeItem::create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'RW-TAG-DASH-1',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 100000,
            'live_weight_kg' => 250,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $plan = SlaughterPlan::create([
            'slaughter_date' => $slaughterTime->toDateString(),
            'facility_id' => $this->slaughterFacility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 2,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $execution = SlaughterExecution::create([
            'slaughter_plan_id' => $plan->id,
            'actual_animals_slaughtered' => 2,
            'slaughter_time' => $slaughterTime,
            'status' => SlaughterExecution::STATUS_COMPLETED,
        ]);

        $executionItem = SlaughterExecutionItem::create([
            'slaughter_execution_id' => $execution->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 130.5,
        ]);

        $batch = Batch::create([
            'slaughter_execution_id' => $execution->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 130.5,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-DASH-001',
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        $batchItem = BatchItem::create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $executionItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 130.5,
        ]);

        $pm = PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 2,
            'approved_quantity' => 1,
            'condemned_quantity' => 1,
            'inspection_date' => $slaughterTime->toDateString(),
            'result' => PostMortemInspection::RESULT_APPROVED,
        ]);

        PostMortemInspectionItem::create([
            'post_mortem_inspection_id' => $pm->id,
            'batch_item_id' => $batchItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'outcome' => PostMortemInspectionItem::OUTCOME_CONDEMNED,
        ]);

        Certificate::create([
            'batch_id' => $batch->id,
            'inspector_id' => $inspector->id,
            'facility_id' => $this->slaughterFacility->id,
            'slaughterhouse_display_name' => $this->slaughterFacility->facility_name,
            'certificate_number' => 'CERT-DASH-001',
            'issued_at' => $slaughterTime,
            'status' => Certificate::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('rica.slaughterhouses.show', $this->slaughterFacility));

        $response->assertOk()
            ->assertSee('Animals slaughtered')
            ->assertSee('Total meat yield')
            ->assertSee('130.50 kg')
            ->assertSee('Cattle')
            ->assertSee('Species breakdown')
            ->assertSee('Animals condemned at PM');
    }
}
