<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\SlaughterPlan;
use App\Models\User;
use App\Services\SuperAdmin\RicaAlertsNotificationsDashboardService;
use App\Support\TenantEnvironmentScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RicaAlertsNotificationsDashboardTest extends TestCase
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
            'license_number' => 'LIC-ALERT-001',
            'license_issue_date' => now()->subYear(),
            'license_expiry_date' => now()->subMonth(),
            'status' => Facility::STATUS_ACTIVE,
        ]);

        $this->inspector = Inspector::create([
            'facility_id' => $this->slaughterFacility->id,
            'first_name' => 'Alert',
            'last_name' => 'Inspector',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'alert-inspector-'.uniqid().'@test.com',
            'dob' => '1985-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'authorization_number' => 'PMI-ALERT-001',
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

    public function test_alerts_notifications_dashboard_shows_expired_licence_alert(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('rica.alerts-notifications'));

        $response->assertOk()
            ->assertSee('Alerts & notifications')
            ->assertSee('Alert inbox')
            ->assertSee('Expired facility licence')
            ->assertSee('Nyagatare Modern Slaughter House');

        $dashboard = app(RicaAlertsNotificationsDashboardService::class)->build(
            Request::create('/rica/alerts-notifications', 'GET')
        );

        $this->assertGreaterThan(0, $dashboard['kpis']['critical']);
        $this->assertTrue(
            collect($dashboard['alerts'])->contains(
                fn (array $alert) => $alert['category'] === RicaAlertsNotificationsDashboardService::CATEGORY_LICENCES
            )
        );
    }

    public function test_alerts_notifications_dashboard_shows_overdue_monthly_report(): void
    {
        $previousMonth = now()->subMonth()->startOfMonth();

        SlaughterPlan::create([
            'facility_id' => $this->slaughterFacility->id,
            'inspector_id' => $this->inspector->id,
            'slaughter_date' => $previousMonth->copy()->addDays(2)->toDateString(),
            'species' => 'cattle',
            'number_of_animals_scheduled' => 3,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => $previousMonth->year,
            'period_month' => $previousMonth->month,
            'status' => RicaMonthlyInspectionReport::STATUS_DRAFT,
        ]);

        $dashboard = app(RicaAlertsNotificationsDashboardService::class)->build(
            Request::create('/rica/alerts-notifications', 'GET')
        );

        $this->assertTrue(
            collect($dashboard['alerts'])->contains(
                fn (array $alert) => $alert['category'] === RicaAlertsNotificationsDashboardService::CATEGORY_MONTHLY_REPORTS
                    && $alert['severity'] === RicaAlertsNotificationsDashboardService::SEVERITY_CRITICAL
            )
        );
    }
}
