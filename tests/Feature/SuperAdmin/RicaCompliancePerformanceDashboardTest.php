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
use App\Models\RicaMonthlyInspectionReport;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\User;
use App\Services\SuperAdmin\RicaCompliancePerformanceDashboardService;
use App\Support\TenantEnvironmentScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RicaCompliancePerformanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Facility $slaughterFacility;

    private Inspector $inspector;

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
            'license_number' => 'LIC-CP-001',
            'license_issue_date' => now()->subYear(),
            'status' => Facility::STATUS_ACTIVE,
        ]);

        $this->inspector = Inspector::create([
            'facility_id' => $this->slaughterFacility->id,
            'first_name' => 'James',
            'last_name' => 'Kamana',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'vet-cp-'.uniqid().'@test.com',
            'dob' => '1985-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'authorization_number' => 'PMI-CP-001',
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => Inspector::STATUS_ACTIVE,
        ]);
    }

    protected function tearDown(): void
    {
        TenantEnvironmentScope::resetFilter();
        parent::tearDown();
    }

    public function test_compliance_performance_dashboard_shows_metrics(): void
    {
        $this->seedInspectionPipeline(now()->startOfMonth()->addDays(2), 100.0, 0.0);
        $this->seedSubmittedMonthlyReport();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('rica.compliance-performance', ['period' => 'month']));

        $response->assertOk()
            ->assertSee('Compliance performance')
            ->assertSee('Active PMIs')
            ->assertSee('Slaughterhouse ranking')
            ->assertSee('PMI ranking')
            ->assertSee('Nyagatare Modern Slaughter House')
            ->assertSee('James Kamana');

        $dashboard = app(RicaCompliancePerformanceDashboardService::class)->build(
            Request::create('/rica/compliance-performance', 'GET', ['period' => 'month'])
        );

        $this->assertGreaterThan(0, $dashboard['kpis']['active_pmis']['value']);
        $this->assertGreaterThan(0, $dashboard['kpis']['reports_submitted']['value']);
        $this->assertNotEmpty($dashboard['slaughterhouseRows']);
        $this->assertNotEmpty($dashboard['pmiRows']);
    }

    private function seedSubmittedMonthlyReport(): void
    {
        $period = now()->startOfMonth();

        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => $period->year,
            'period_month' => $period->month,
            'inspector_signatures' => [
                ['name' => 'James Kamana', 'signed_at' => $period->copy()->endOfMonth()->toIso8601String()],
            ],
            'operator_name' => 'Operations Manager',
            'operator_signed_at' => $period->copy()->endOfMonth(),
            'stamp_acknowledged' => true,
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => $period->copy()->endOfMonth(),
        ]);
    }

    private function seedInspectionPipeline(\Carbon\Carbon $inspectionDate, float $approvedKg, float $condemnedKg): void
    {
        $intake = AnimalIntake::query()->create([
            'facility_id' => $this->slaughterFacility->id,
            'intake_date' => $inspectionDate->copy()->subDays(2),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'unit_price' => 250000,
            'total_price' => 250000,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
            'supplier_firstname' => 'Test',
            'supplier_lastname' => 'Supplier',
            'supplier_contact' => '+250788000001',
            'farm_name' => 'Compliance test farm',
        ]);

        $intakeItem = AnimalIntakeItem::query()->create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'CP-'.uniqid(),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'live_weight_kg' => 420,
            'unit_price' => 250000,
            'service_fee' => 15000,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $plan = SlaughterPlan::query()->create([
            'slaughter_date' => $inspectionDate->toDateString(),
            'facility_id' => $this->slaughterFacility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $execution = SlaughterExecution::query()->create([
            'slaughter_plan_id' => $plan->id,
            'actual_animals_slaughtered' => 1,
            'slaughter_time' => $inspectionDate->copy()->setTime(8, 0),
            'status' => SlaughterExecution::STATUS_COMPLETED,
            'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
        ]);

        $executionItem = SlaughterExecutionItem::query()->create([
            'slaughter_execution_id' => $execution->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => $approvedKg + $condemnedKg,
        ]);

        $batch = Batch::query()->create([
            'slaughter_execution_id' => $execution->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => $approvedKg + $condemnedKg,
            'quantity_unit' => 'kg',
            'status' => Batch::STATUS_APPROVED,
        ]);

        $batchItem = BatchItem::query()->create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $executionItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => $approvedKg + $condemnedKg,
        ]);

        $inspection = PostMortemInspection::query()->create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'inspection_date' => $inspectionDate->toDateString(),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'approved_quantity' => 0,
            'condemned_quantity' => 0,
        ]);

        PostMortemInspectionItem::query()->create([
            'post_mortem_inspection_id' => $inspection->id,
            'animal_intake_item_id' => $intakeItem->id,
            'batch_item_id' => $batchItem->id,
            'outcome' => PostMortemInspectionItem::OUTCOME_APPROVED,
            'carcass_weight_kg' => $approvedKg,
        ]);

        if ($condemnedKg > 0) {
            PostMortemInspectionItem::query()->create([
                'post_mortem_inspection_id' => $inspection->id,
                'animal_intake_item_id' => $intakeItem->id,
                'batch_item_id' => $batchItem->id,
                'outcome' => PostMortemInspectionItem::OUTCOME_CONDEMNED,
                'condemned_weight_kg' => $condemnedKg,
                'seized_part' => 'Liver',
                'reason' => 'Abscess',
            ]);
        }
    }
}
