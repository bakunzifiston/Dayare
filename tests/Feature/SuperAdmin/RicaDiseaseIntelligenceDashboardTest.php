<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use App\Models\User;
use App\Services\SuperAdmin\RicaDiseaseIntelligenceDashboardService;
use App\Support\TenantEnvironmentScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RicaDiseaseIntelligenceDashboardTest extends TestCase
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
            'license_number' => 'LIC-DI-001',
            'license_issue_date' => now()->subYear(),
            'status' => Facility::STATUS_ACTIVE,
        ]);
    }

    protected function tearDown(): void
    {
        TenantEnvironmentScope::resetFilter();
        parent::tearDown();
    }

    public function test_disease_intelligence_dashboard_shows_case_metrics(): void
    {
        $this->seedRejectedAnteCase(
            now()->startOfMonth()->addDays(3),
            'Salivation, vesicles on mouth and feet; suspected FMD',
            AnimalIntake::SPECIES_CATTLE,
        );

        $response = $this->actingAs($this->superAdmin)
            ->get(route('rica.diseases-intelligence', ['period' => 'month']));

        $response->assertOk()
            ->assertSee('Disease intelligence')
            ->assertSee('Foot and Mouth Disease')
            ->assertSee('Nyagatare');

        $dashboard = app(RicaDiseaseIntelligenceDashboardService::class)->build(
            Request::create('/rica/diseases-intelligence', 'GET', ['period' => 'month'])
        );

        $this->assertGreaterThan(0, $dashboard['kpis']['disease_cases']['value']);
        $this->assertGreaterThan(0, $dashboard['kpis']['diseases_detected']['value']);
        $speciesChart = collect($dashboard['chartSpecs'])->firstWhere('id', 'rica-di-species-donut');
        $this->assertNotEmpty($speciesChart['legend'] ?? []);
        $this->assertContains('Cattle', $speciesChart['labels'] ?? []);
        $this->assertTrue(
            collect($dashboard['districtMap'])->contains(
                fn (array $district) => $district['name'] === 'Nyagatare' && $district['count'] > 0
            )
        );
    }

    private function seedRejectedAnteCase(\Carbon\Carbon $inspectionDate, string $condition, string $species): void
    {
        $inspector = Inspector::create([
            'facility_id' => $this->slaughterFacility->id,
            'first_name' => 'Alice',
            'last_name' => 'Vet',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'vet-di-'.uniqid().'@test.com',
            'dob' => '1985-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'authorization_number' => 'AUTH-DI',
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => Inspector::STATUS_ACTIVE,
        ]);

        $intake = AnimalIntake::query()->create([
            'facility_id' => $this->slaughterFacility->id,
            'intake_date' => $inspectionDate->copy()->subDay(),
            'species' => $species,
            'number_of_animals' => 1,
            'unit_price' => 250000,
            'total_price' => 250000,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
            'supplier_firstname' => 'Test',
            'supplier_lastname' => 'Supplier',
            'supplier_contact' => '+250788000001',
            'farm_name' => 'Disease intelligence test farm',
        ]);

        $animal = AnimalIntakeItem::query()->create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'DI-'.uniqid(),
            'species' => $species,
            'sex' => AnimalIntake::SEX_MALE,
            'live_weight_kg' => 420,
            'unit_price' => 250000,
            'service_fee' => 15000,
            'health_status' => AnimalIntakeItem::HEALTH_OBSERVATION,
        ]);

        $plan = SlaughterPlan::query()->create([
            'slaughter_date' => $inspectionDate->toDateString(),
            'facility_id' => $this->slaughterFacility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => $species,
            'number_of_animals_scheduled' => 0,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $ante = AnteMortemInspection::query()->create([
            'slaughter_plan_id' => $plan->id,
            'inspector_id' => $inspector->id,
            'species' => $species,
            'number_examined' => 1,
            'number_approved' => 0,
            'number_rejected' => 1,
            'inspection_date' => $inspectionDate->toDateString(),
            'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
        ]);

        AnteMortemInspectionItem::query()->create([
            'ante_mortem_inspection_id' => $ante->id,
            'animal_intake_item_id' => $animal->id,
            'outcome' => AnteMortemInspectionItem::OUTCOME_REJECTED,
            'conditions' => $condition,
            'action_taken' => 'Returned to supplier',
        ]);
    }
}
