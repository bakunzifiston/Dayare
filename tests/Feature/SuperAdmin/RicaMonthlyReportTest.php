<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\AnteMortemObservation;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\WarehouseStorage;
use App\Models\RicaMonthlyInspectionReport;
use App\Services\SuperAdmin\RicaMonthlyInspectionReportService;
use App\Support\TenantEnvironmentScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RicaMonthlyReportTest extends TestCase
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

    public function test_rica_only_super_admin_home_redirects_to_rica_workspace(): void
    {
        $ricaAdmin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_RICA],
        ]);

        $this->assertSame('rica.dashboard', $ricaAdmin->defaultDashboardRouteName());

        $this->actingAs($ricaAdmin)
            ->get(route('home'))
            ->assertRedirect(route('rica.dashboard'));

        $this->actingAs($ricaAdmin)
            ->get(route('rica.dashboard'))
            ->assertOk()
            ->assertSee('National Meat Inspection Overview')
            ->assertSee('Slaughtered species trend')
            ->assertSee('Animals by district')
            ->assertSee('Animals received by slaughterhouse')
            ->assertSee('Animals slaughtered by slaughterhouse');

        $this->actingAs($ricaAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($ricaAdmin)
            ->get(route('rica.settings'))
            ->assertOk()
            ->assertSee('Settings');

        $this->actingAs($ricaAdmin)
            ->get(route('settings.edit'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_monthly_reports_index(): void
    {
        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => now()->subMonth()->year,
            'period_month' => now()->subMonth()->month,
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => now()->subMonth(),
            'submitted_by_user_id' => $this->superAdmin->id,
            'inspector_signatures' => [['name' => 'Alice Vet', 'signed_at' => now()->toIso8601String()]],
            'operator_name' => 'Jean Operator',
            'operator_signed_at' => now(),
            'stamp_acknowledged' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('rica.monthly-reports.index'))
            ->assertOk()
            ->assertSee('Monthly inspection reports')
            ->assertSee('Submitted to RICA')
            ->assertSee('All periods')
            ->assertSee($this->slaughterFacility->facility_name)
            ->assertSee('across all periods');
    }

    public function test_monthly_report_index_lists_test_tenant_slaughterhouses_on_facilities_tab(): void
    {
        $testUser = User::factory()->create([
            'tenant_environment' => User::TENANT_ENVIRONMENT_TEST,
        ]);
        $business = Business::factory()->create(['user_id' => $testUser->id]);
        Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Test Tenant Slaughter House',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'status' => Facility::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('rica.monthly-reports.index', ['view' => 'facilities']))
            ->assertOk()
            ->assertSee('Test Tenant Slaughter House');
    }

    public function test_monthly_report_index_can_be_scoped_to_facility(): void
    {
        $otherBusiness = Business::factory()->create(['user_id' => User::factory()->create()->id]);
        $otherFacility = Facility::create([
            'business_id' => $otherBusiness->id,
            'facility_name' => 'Other Slaughter House',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'status' => Facility::STATUS_ACTIVE,
        ]);

        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => now()->subMonths(2)->year,
            'period_month' => now()->subMonths(2)->month,
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => now()->subMonths(2),
            'submitted_by_user_id' => $this->superAdmin->id,
            'inspector_signatures' => [['name' => 'Alice Vet', 'signed_at' => now()->toIso8601String()]],
            'operator_name' => 'Jean Operator',
            'operator_signed_at' => now(),
            'stamp_acknowledged' => true,
        ]);

        RicaMonthlyInspectionReport::create([
            'facility_id' => $otherFacility->id,
            'period_year' => now()->subMonth()->year,
            'period_month' => now()->subMonth()->month,
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => now()->subMonth(),
            'submitted_by_user_id' => $this->superAdmin->id,
            'inspector_signatures' => [['name' => 'Bob Vet', 'signed_at' => now()->toIso8601String()]],
            'operator_name' => 'Other Operator',
            'operator_signed_at' => now(),
            'stamp_acknowledged' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('rica.monthly-reports.index', ['facility_id' => $this->slaughterFacility->id]))
            ->assertOk()
            ->assertSee($this->slaughterFacility->facility_name)
            ->assertSee('across all periods')
            ->assertDontSee('Other Slaughter House');
    }

    public function test_monthly_report_index_includes_facility_with_slaughter_plans_even_if_not_slaughterhouse_type(): void
    {
        $business = Business::factory()->create(['user_id' => User::factory()->create()->id]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Other Type With Slaughter',
            'facility_type' => Facility::TYPE_OTHER,
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'status' => Facility::STATUS_ACTIVE,
        ]);

        SlaughterPlan::create([
            'slaughter_date' => now()->toDateString(),
            'facility_id' => $facility->id,
            'inspector_id' => Inspector::create([
                'facility_id' => $facility->id,
                'first_name' => 'Test',
                'last_name' => 'Inspector',
                'national_id' => (string) random_int(100000000000, 999999999999),
                'phone_number' => '+250788123458',
                'email' => 'test-inspector@test.com',
                'dob' => '1985-01-01',
                'nationality' => 'Rwandan',
                'country' => 'Rwanda',
                'district' => 'Nyagatare',
                'sector' => 'Rwimiyaga',
                'authorization_number' => 'AUTH-TEST',
                'authorization_issue_date' => now()->subYear(),
                'authorization_expiry_date' => now()->addYear(),
                'species_allowed' => 'Cattle',
                'status' => Inspector::STATUS_ACTIVE,
            ])->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('rica.monthly-reports.index', ['view' => 'facilities']))
            ->assertOk()
            ->assertSee('Other Type With Slaughter');
    }

    public function test_rica_module_pages_are_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('rica.traceability'))
            ->assertOk()
            ->assertSee('Traceability overview')
            ->assertSee('Farm to destination journey');

        $this->actingAs($this->superAdmin)
            ->get(route('rica.diseases-intelligence'))
            ->assertOk()
            ->assertSee('Disease intelligence')
            ->assertSee('Unhealthy animals')
            ->assertSee('Top diseases');

        $this->actingAs($this->superAdmin)
            ->get(route('rica.meat-condemnation'))
            ->assertOk()
            ->assertSee('Meat condemnation')
            ->assertSee('Rejected meat')
            ->assertSee('Rejection by slaughterhouse');

        $this->actingAs($this->superAdmin)
            ->get(route('rica.supply-chain'))
            ->assertOk()
            ->assertSee('Supply chain & distribution dashboard')
            ->assertSee('Meat delivered (kg)')
            ->assertSee('Rwanda destinations map');

        $this->actingAs($this->superAdmin)
            ->get(route('rica.compliance-performance'))
            ->assertOk()
            ->assertSee('Compliance performance')
            ->assertSee('Reports submitted');

        $this->actingAs($this->superAdmin)
            ->get(route('rica.alerts-notifications'))
            ->assertOk()
            ->assertSee('Alerts & notifications')
            ->assertSee('Alert inbox');

        $this->actingAs($this->superAdmin)
            ->get(route('rica.settings'))
            ->assertOk()
            ->assertSee('Settings');
    }

    public function test_rica_workspace_settings_can_be_updated(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('rica.settings.update'), [
                'workspace_name' => 'RICA National Oversight',
                'default_tenant_environment' => 'live',
                'default_dashboard_period' => 'month',
                'notification_email' => 'rica@example.com',
                'monthly_report_deadline_day' => 10,
                'condemnation_loss_per_kg_rwf' => 4200,
            ])
            ->assertRedirect(route('rica.settings'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('rica_settings', [
            'key' => 'workspace_name',
            'value' => 'RICA National Oversight',
        ]);

        $this->assertDatabaseHas('rica_settings', [
            'key' => 'notification_email',
            'value' => 'rica@example.com',
        ]);

        $this->assertDatabaseHas('rica_settings', [
            'key' => 'condemnation_loss_per_kg_rwf',
            'value' => '4200',
        ]);
    }

    public function test_monthly_report_show_displays_form_sections(): void
    {
        $inspector = Inspector::create([
            'facility_id' => $this->slaughterFacility->id,
            'first_name' => 'Alice',
            'last_name' => 'Vet',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788123456',
            'email' => 'vet@test.com',
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

        $intake = AnimalIntake::create([
            'facility_id' => $this->slaughterFacility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Jean',
            'supplier_lastname' => 'Supplier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'district_id' => $this->slaughterFacility->district_id,
            'sector_id' => $this->slaughterFacility->sector_id,
            'cell_id' => $this->slaughterFacility->cell_id,
        ]);

        $intakeItem = AnimalIntakeItem::create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'RW-TAG-RICA-1',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'notes' => 'Healthy bull from supplier',
            'unit_price' => 100000,
            'live_weight_kg' => 250,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $plan = SlaughterPlan::create([
            'slaughter_date' => now()->toDateString(),
            'facility_id' => $this->slaughterFacility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $anteMortemInspection = AnteMortemInspection::create([
            'slaughter_plan_id' => $plan->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_examined' => 1,
            'number_approved' => 1,
            'number_rejected' => 0,
            'inspection_date' => now()->toDateString(),
            'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
        ]);

        AnteMortemInspectionItem::create([
            'ante_mortem_inspection_id' => $anteMortemInspection->id,
            'animal_intake_item_id' => $intakeItem->id,
            'outcome' => AnteMortemInspectionItem::OUTCOME_APPROVED,
            'outcome_notes' => 'Fit for slaughter',
        ]);

        AnteMortemObservation::create([
            'ante_mortem_inspection_id' => $anteMortemInspection->id,
            'animal_intake_item_id' => $intakeItem->id,
            'item' => 'locomotion',
            'value' => 'normal',
        ]);

        $execution = SlaughterExecution::create([
            'slaughter_plan_id' => $plan->id,
            'actual_animals_slaughtered' => 1,
            'slaughter_time' => now(),
            'status' => SlaughterExecution::STATUS_COMPLETED,
        ]);

        $executionItem = SlaughterExecutionItem::create([
            'slaughter_execution_id' => $execution->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 120,
        ]);

        $batch = Batch::create([
            'slaughter_execution_id' => $execution->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 120,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-RICA-'.uniqid(),
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        $batchItem = BatchItem::create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $executionItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 120,
        ]);

        $postMortemInspection = PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 120,
            'approved_quantity' => 120,
            'condemned_quantity' => 0,
            'inspection_date' => today(),
            'result' => PostMortemInspection::RESULT_APPROVED,
        ]);

        PostMortemInspectionItem::create([
            'post_mortem_inspection_id' => $postMortemInspection->id,
            'batch_item_id' => $batchItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'outcome' => PostMortemInspectionItem::OUTCOME_APPROVED,
            'carcass_weight_kg' => 110,
            'outcome_notes' => 'Carcass approved',
        ]);

        $storageFacility = Facility::create([
            'business_id' => $this->slaughterFacility->business_id,
            'facility_name' => 'Cold Storage',
            'facility_type' => Facility::TYPE_STORAGE,
            'status' => Facility::STATUS_ACTIVE,
        ]);

        WarehouseStorage::create([
            'warehouse_facility_id' => $storageFacility->id,
            'batch_id' => $batch->id,
            'animal_intake_item_id' => $intakeItem->id,
            'entry_date' => now()->toDateString(),
            'quantity_stored' => 110,
            'quantity_unit' => 'kg',
            'status' => WarehouseStorage::STATUS_RELEASED,
            'released_date' => now()->toDateString(),
        ]);

        Certificate::create([
            'batch_id' => $batch->id,
            'inspector_id' => $inspector->id,
            'facility_id' => $this->slaughterFacility->id,
            'slaughterhouse_display_name' => $this->slaughterFacility->facility_name,
            'certificate_number' => 'CERT-RICA-001',
            'issued_at' => now(),
            'status' => Certificate::STATUS_ACTIVE,
            'pdf_details' => [
                'selling_location' => 'Nyagatare, Rwimiyaga',
                'carcass_meat_kg' => 110,
            ],
        ]);

        $month = now()->format('Y-m');

        $this->actingAs($this->superAdmin)
            ->get(route('rica.monthly-reports.show', [
                'facility' => $this->slaughterFacility,
                'month' => $month,
            ]))
            ->assertOk()
            ->assertSee(RicaMonthlyInspectionReportService::FORM_ID)
            ->assertSee('PRIVATE MEAT INSPECTOR DETAILS')
            ->assertSee('SLAUGHTERHOUSE DETAILS')
            ->assertSee('RECEIVED ANIMALS DETAILS')
            ->assertSee('ANTE-MORTEM INSPECTION DETAILS')
            ->assertSee('No. of healthy animals')
            ->assertSee('POST-MORTEM INSPECTION DETAILS')
            ->assertSee('No. of approved carcasses')
            ->assertSee('MEAT SUPPLY DETAILS')
            ->assertSee('Alice Vet')
            ->assertSee('CERT-RICA-001')
            ->assertDontSee('No certificates issued in this period.')
            ->assertSee('110.00')
            ->assertSee('Nyagatare, Rwimiyaga, Nyagatare Cell')
            ->assertSee('Healthy bull from supplier');
    }

    public function test_monthly_report_meat_supply_works_without_batch(): void
    {
        Certificate::create([
            'batch_id' => null,
            'inspector_id' => Inspector::create([
                'facility_id' => $this->slaughterFacility->id,
                'first_name' => 'Bob',
                'last_name' => 'Inspector',
                'national_id' => (string) random_int(100000000000, 999999999999),
                'phone_number' => '+250788123457',
                'email' => 'bob@test.com',
                'dob' => '1985-01-01',
                'nationality' => 'Rwandan',
                'country' => 'Rwanda',
                'district' => 'Nyagatare',
                'sector' => 'Rwimiyaga',
                'authorization_number' => 'AUTH-002',
                'authorization_issue_date' => now()->subYear(),
                'authorization_expiry_date' => now()->addYear(),
                'species_allowed' => 'Cattle',
                'status' => Inspector::STATUS_ACTIVE,
            ])->id,
            'facility_id' => $this->slaughterFacility->id,
            'slaughterhouse_display_name' => $this->slaughterFacility->facility_name,
            'certificate_number' => 'CERT-NO-BATCH-001',
            'issued_at' => now(),
            'status' => Certificate::STATUS_ACTIVE,
            'pdf_details' => [
                'species' => AnimalIntake::SPECIES_CATTLE,
                'selling_location' => 'Nyagatare, Rwimiyaga, Katabagemu',
                'carcass_meat_kg' => 85,
                'other_meat_kg' => 5,
            ],
        ]);

        $month = now()->format('Y-m');

        $this->actingAs($this->superAdmin)
            ->get(route('rica.monthly-reports.show', [
                'facility' => $this->slaughterFacility,
                'month' => $month,
            ]))
            ->assertOk()
            ->assertSee('CERT-NO-BATCH-001')
            ->assertSee('90.00')
            ->assertSee('Katabagemu')
            ->assertDontSee('No certificates issued in this period.');
    }

    public function test_monthly_report_pdf_downloads(): void
    {
        $inspector = Inspector::create([
            'facility_id' => $this->slaughterFacility->id,
            'first_name' => 'Alice',
            'last_name' => 'Vet',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788123456',
            'email' => 'vet@test.com',
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

        $intake = AnimalIntake::create([
            'facility_id' => $this->slaughterFacility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Jean',
            'supplier_lastname' => 'Supplier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);

        $intakeItem = AnimalIntakeItem::create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'RW-TAG-PDF-1',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 100000,
            'live_weight_kg' => 250,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $plan = SlaughterPlan::create([
            'slaughter_date' => now()->toDateString(),
            'facility_id' => $this->slaughterFacility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $execution = SlaughterExecution::create([
            'slaughter_plan_id' => $plan->id,
            'actual_animals_slaughtered' => 1,
            'slaughter_time' => now(),
            'status' => SlaughterExecution::STATUS_COMPLETED,
        ]);

        SlaughterExecutionItem::create([
            'slaughter_execution_id' => $execution->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 120,
        ]);

        $month = now()->format('Y-m');

        $this->actingAs($this->superAdmin)
            ->get(route('rica.monthly-reports.pdf', [
                'facility' => $this->slaughterFacility,
                'month' => $month,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_rica_monthly_report_show_is_read_only_for_closure_sections(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('rica.monthly-reports.show', [
                'facility' => $this->slaughterFacility,
                'month' => now()->format('Y-m'),
            ]))
            ->assertOk()
            ->assertDontSee('Submit to RICA', false)
            ->assertSee('Sections 7–8 have not been submitted yet');
    }

    public function test_monthly_report_meat_supply_uses_client_location_for_external_trips(): void
    {
        $business = $this->slaughterFacility->business;
        $client = Client::create([
            'business_id' => $business->id,
            'name' => 'Remera Wholesale Market',
            'email' => 'remera@market.rw',
            'phone' => '+250788111222',
            'country' => 'Rwanda',
            'district_id' => $this->slaughterFacility->district_id,
            'sector_id' => $this->slaughterFacility->sector_id,
            'is_active' => true,
        ]);

        $certificate = Certificate::create([
            'batch_id' => null,
            'inspector_id' => Inspector::create([
                'facility_id' => $this->slaughterFacility->id,
                'first_name' => 'Claire',
                'last_name' => 'Inspector',
                'national_id' => (string) random_int(100000000000, 999999999999),
                'phone_number' => '+250788123459',
                'email' => 'claire@test.com',
                'dob' => '1985-01-01',
                'nationality' => 'Rwandan',
                'country' => 'Rwanda',
                'district' => 'Nyagatare',
                'sector' => 'Rwimiyaga',
                'authorization_number' => 'AUTH-003',
                'authorization_issue_date' => now()->subYear(),
                'authorization_expiry_date' => now()->addYear(),
                'species_allowed' => 'Cattle',
                'status' => Inspector::STATUS_ACTIVE,
            ])->id,
            'facility_id' => $this->slaughterFacility->id,
            'certificate_number' => 'CERT-TRANSPORT-LOC-001',
            'issued_at' => now(),
            'status' => Certificate::STATUS_ACTIVE,
            'pdf_details' => [
                'species' => AnimalIntake::SPECIES_CATTLE,
                'carcass_meat_kg' => 120,
            ],
        ]);

        $trip = TransportTrip::create([
            'certificate_id' => $certificate->id,
            'batch_id' => $certificate->batch_id,
            'origin_facility_id' => $this->slaughterFacility->id,
            'destination_name' => $client->name,
            'destination_country' => 'RW',
            'destination_address' => 'Ignored when client is linked',
            'vehicle_plate_number' => 'RAD 412 B',
            'driver_name' => 'Jean Transport',
            'departure_date' => now(),
            'arrival_date' => now(),
            'status' => TransportTrip::STATUS_ARRIVED,
        ]);

        DeliveryConfirmation::create([
            'transport_trip_id' => $trip->id,
            'client_id' => $client->id,
            'received_quantity' => 120,
            'received_unit' => 'kg',
            'received_date' => now(),
            'receiver_name' => $client->name,
            'receiver_country' => 'RW',
            'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
        ]);

        $report = app(RicaMonthlyInspectionReportService::class)->build(
            $this->slaughterFacility,
            now()->startOfMonth(),
            now()->endOfMonth(),
        );

        $row = collect($report['meat_supply']['rows'])->firstWhere('certificate_number', 'CERT-TRANSPORT-LOC-001');
        $this->assertNotNull($row);
        $this->assertSame('Nyagatare', $row['destination_district']);
        $this->assertSame('Rwimiyaga', $row['destination_sector']);
        $this->assertSame('Remera Wholesale Market', $row['destination_other']);
    }
}
