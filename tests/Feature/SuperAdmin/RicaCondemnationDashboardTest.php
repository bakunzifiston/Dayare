<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\RicaSetting;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\User;
use App\Services\SuperAdmin\RicaCondemnationDashboardService;
use App\Support\TenantEnvironmentScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RicaCondemnationDashboardTest extends TestCase
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
        $district = AdministrativeDivision::create([
            'name' => 'Nyagatare',
            'type' => AdministrativeDivision::TYPE_DISTRICT,
        ]);

        $this->slaughterFacility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Nyagatare Modern Slaughter House',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'district' => 'Nyagatare',
            'district_id' => $district->id,
            'license_number' => 'LIC-COND-001',
            'license_issue_date' => now()->subYear(),
            'status' => Facility::STATUS_ACTIVE,
        ]);
    }

    protected function tearDown(): void
    {
        TenantEnvironmentScope::resetFilter();
        parent::tearDown();
    }

    public function test_meat_condemnation_dashboard_shows_current_month_data(): void
    {
        $this->seedCondemnationInspection(now()->startOfMonth()->addDays(2), condemnedKg: 14.5, approvedKg: 180.0);
        $this->seedCondemnationInspection(now()->startOfMonth()->addDays(8), condemnedKg: 9.0, approvedKg: 210.0);
        $this->seedCondemnationInspection(now()->subMonth()->startOfMonth()->addDays(4), condemnedKg: 11.0, approvedKg: 160.0);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('rica.meat-condemnation', ['period' => 'month']));

        $response->assertOk()
            ->assertSee('Meat condemnation')
            ->assertSee('Liver')
            ->assertSee('Nyagatare Modern Slaughter House')
            ->assertSee('24'); // rejected kg KPI (rounded)

        $dashboard = app(RicaCondemnationDashboardService::class)->build(
            Request::create('/rica/meat-condemnation', 'GET', ['period' => 'month'])
        );

        $this->assertSame(23.5, $dashboard['kpis']['rejected_meat_kg']['value']);
        $this->assertSame(2, $dashboard['kpis']['rejection_cases']['value']);
        $this->assertGreaterThan(0, $dashboard['kpis']['economic_loss']['value']);
        $this->assertNotEmpty($dashboard['slaughterhouseRows']);
        $this->assertNotEmpty($dashboard['economicLossRows']);

        $organChart = collect($dashboard['chartSpecs'])->firstWhere('id', 'rica-cond-organ-donut');
        $reasonChart = collect($dashboard['chartSpecs'])->firstWhere('id', 'rica-cond-reasons-bar');
        $speciesChart = collect($dashboard['chartSpecs'])->firstWhere('id', 'rica-cond-species-bar');

        $this->assertSame(['Liver'], $organChart['labels']);
        $this->assertSame([23.5], $organChart['data']);
        $this->assertNotEmpty($organChart['legend']);
        $this->assertNotEmpty($reasonChart['labels']);
        $this->assertSame(['Cattle'], $speciesChart['labels']);
        $this->assertSame([23.5], $speciesChart['datasets'][0]['data']);
        $this->assertSame('bar', $speciesChart['type']);
        $this->assertSame('y', $speciesChart['indexAxis']);

        RicaSetting::setMany(['condemnation_loss_per_kg_rwf' => '5000']);
        $dashboardWithCustomRate = app(RicaCondemnationDashboardService::class)->build(
            Request::create('/rica/meat-condemnation', 'GET', ['period' => 'month'])
        );
        $this->assertSame(23.5 * 5000, $dashboardWithCustomRate['kpis']['economic_loss']['value']);
    }

    private function seedCondemnationInspection(
        \Carbon\Carbon $inspectionDate,
        float $condemnedKg,
        float $approvedKg,
    ): void {
        $inspector = Inspector::create([
            'facility_id' => $this->slaughterFacility->id,
            'first_name' => 'Alice',
            'last_name' => 'Vet',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'vet-cond-'.uniqid().'@test.com',
            'dob' => '1985-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'authorization_number' => 'AUTH-COND',
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => Inspector::STATUS_ACTIVE,
        ]);

        $intake = AnimalIntake::query()->create([
            'facility_id' => $this->slaughterFacility->id,
            'intake_date' => $inspectionDate->copy()->subDays(2),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 2,
            'unit_price' => 250000,
            'total_price' => 500000,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
            'supplier_firstname' => 'Test',
            'supplier_lastname' => 'Supplier',
            'supplier_contact' => '+250788000001',
            'farm_name' => 'Condemnation test farm',
        ]);

        $approvedAnimal = AnimalIntakeItem::query()->create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'COND-AP-'.uniqid(),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'live_weight_kg' => 420,
            'unit_price' => 250000,
            'service_fee' => 15000,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $condemnedAnimal = AnimalIntakeItem::query()->create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'COND-CD-'.uniqid(),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_FEMALE,
            'live_weight_kg' => 400,
            'unit_price' => 250000,
            'service_fee' => 15000,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $plan = SlaughterPlan::query()->create([
            'slaughter_date' => $inspectionDate->toDateString(),
            'facility_id' => $this->slaughterFacility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 2,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $execution = SlaughterExecution::query()->create([
            'slaughter_plan_id' => $plan->id,
            'actual_animals_slaughtered' => 2,
            'slaughter_time' => $inspectionDate->copy()->setTime(8, 0),
            'status' => SlaughterExecution::STATUS_COMPLETED,
            'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
        ]);

        $approvedExecutionItem = SlaughterExecutionItem::query()->create([
            'slaughter_execution_id' => $execution->id,
            'animal_intake_item_id' => $approvedAnimal->id,
            'meat_quantity_kg' => $approvedKg,
        ]);

        $condemnedExecutionItem = SlaughterExecutionItem::query()->create([
            'slaughter_execution_id' => $execution->id,
            'animal_intake_item_id' => $condemnedAnimal->id,
            'meat_quantity_kg' => $condemnedKg + 40,
        ]);

        $batch = Batch::query()->create([
            'slaughter_execution_id' => $execution->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => $approvedKg + $condemnedKg + 40,
            'quantity_unit' => 'kg',
            'status' => Batch::STATUS_APPROVED,
        ]);

        $approvedBatchItem = BatchItem::query()->create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $approvedExecutionItem->id,
            'animal_intake_item_id' => $approvedAnimal->id,
            'meat_quantity_kg' => $approvedKg,
        ]);

        $condemnedBatchItem = BatchItem::query()->create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $condemnedExecutionItem->id,
            'animal_intake_item_id' => $condemnedAnimal->id,
            'meat_quantity_kg' => $condemnedKg + 40,
        ]);

        $pm = PostMortemInspection::query()->create([
            'batch_id' => $batch->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => $approvedKg + $condemnedKg + 40,
            'approved_quantity' => $approvedKg,
            'condemned_quantity' => $condemnedKg,
            'inspection_date' => $inspectionDate->toDateString(),
            'result' => PostMortemInspection::RESULT_PARTIAL,
        ]);

        PostMortemInspectionItem::query()->create([
            'post_mortem_inspection_id' => $pm->id,
            'batch_item_id' => $approvedBatchItem->id,
            'animal_intake_item_id' => $approvedAnimal->id,
            'outcome' => PostMortemInspectionItem::OUTCOME_APPROVED,
            'carcass_weight_kg' => round($approvedKg * 0.82, 2),
        ]);

        PostMortemInspectionItem::query()->create([
            'post_mortem_inspection_id' => $pm->id,
            'batch_item_id' => $condemnedBatchItem->id,
            'animal_intake_item_id' => $condemnedAnimal->id,
            'outcome' => PostMortemInspectionItem::OUTCOME_CONDEMNED,
            'condemned_weight_kg' => $condemnedKg,
            'seized_part' => 'Liver',
            'reason' => 'Multifocal abscesses; unfit for human consumption',
        ]);
    }
}
