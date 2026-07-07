<?php

namespace Tests\Feature;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Facility;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyInspectionReportTest extends TestCase
{
    use RefreshDatabase;

    private User $processorUser;

    private Facility $slaughterFacility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processorUser = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $this->processorUser->id]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $this->processorUser->id,
            'role' => BusinessUser::ROLE_ORG_ADMIN,
        ]);

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

    public function test_processor_can_view_monthly_reports_index(): void
    {
        $this->actingAs($this->processorUser)
            ->get(route('monthly-inspection-reports.index'))
            ->assertOk()
            ->assertSee('Monthly inspection reports')
            ->assertSee('Submitted to RICA');

        $this->actingAs($this->processorUser)
            ->get(route('monthly-inspection-reports.index', ['view' => 'facilities']))
            ->assertOk()
            ->assertSee($this->slaughterFacility->facility_name);
    }

    public function test_processor_can_see_submitted_reports_list(): void
    {
        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => now()->subMonth()->year,
            'period_month' => now()->subMonth()->month,
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => now()->subMonth(),
            'submitted_by_user_id' => $this->processorUser->id,
            'inspector_signatures' => [['name' => 'Alice Vet', 'signed_at' => now()->toIso8601String()]],
            'operator_name' => 'Jean Operator',
            'operator_signed_at' => now(),
            'stamp_acknowledged' => true,
        ]);

        $this->actingAs($this->processorUser)
            ->get(route('monthly-inspection-reports.index'))
            ->assertOk()
            ->assertSee('Reporting period')
            ->assertSee('Submitted on')
            ->assertSee($this->slaughterFacility->facility_name)
            ->assertSee($this->processorUser->name)
            ->assertSee('across all periods');
    }

    public function test_processor_can_open_monthly_report_with_closure_form(): void
    {
        $this->actingAs($this->processorUser)
            ->get(route('monthly-inspection-reports.show', [
                'facility' => $this->slaughterFacility,
                'month' => now()->format('Y-m'),
            ]))
            ->assertOk()
            ->assertSee('Complete sections 7–8 below')
            ->assertSee('Submit to RICA');
    }

    public function test_monthly_report_closure_can_be_saved_as_draft(): void
    {
        $month = now()->format('Y-m');

        $this->actingAs($this->processorUser)
            ->post(route('monthly-inspection-reports.closure', $this->slaughterFacility), [
                'month' => $month,
                'challenges' => 'Cold room power outages twice this month.',
                'recommendations' => 'Install backup generator.',
                'inspector_signatures' => [
                    ['name' => 'Alice Vet', 'attest' => '1'],
                ],
                'operator_name' => 'Jean Operator',
                'submit_to_rica' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('rica_monthly_inspection_reports', [
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'status' => RicaMonthlyInspectionReport::STATUS_DRAFT,
            'challenges' => 'Cold room power outages twice this month.',
        ]);
    }

    public function test_monthly_report_can_be_submitted_to_rica(): void
    {
        $month = now()->format('Y-m');

        $this->actingAs($this->processorUser)
            ->post(route('monthly-inspection-reports.closure', $this->slaughterFacility), [
                'month' => $month,
                'challenges' => 'No major issues.',
                'inspector_signatures' => [
                    ['name' => 'Alice Vet', 'attest' => '1'],
                ],
                'operator_name' => 'Jean Operator',
                'operator_attest' => '1',
                'stamp_acknowledged' => '1',
                'submit_to_rica' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $record = RicaMonthlyInspectionReport::findForPeriod(
            $this->slaughterFacility->id,
            now()->startOfMonth()
        );

        $this->assertNotNull($record);
        $this->assertTrue($record->isSubmitted());
        $this->assertSame($this->processorUser->id, $record->submitted_by_user_id);
    }

    public function test_submitted_reports_filter_by_month(): void
    {
        $lastMonth = now()->subMonth();

        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => $lastMonth->year,
            'period_month' => $lastMonth->month,
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => $lastMonth,
            'submitted_by_user_id' => $this->processorUser->id,
            'inspector_signatures' => [['name' => 'Alice Vet', 'signed_at' => now()->toIso8601String()]],
            'operator_name' => 'Jean Operator',
            'operator_signed_at' => now(),
            'stamp_acknowledged' => true,
        ]);

        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => now()->subMonths(3)->year,
            'period_month' => now()->subMonths(3)->month,
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => now()->subMonths(3),
            'submitted_by_user_id' => $this->processorUser->id,
            'inspector_signatures' => [['name' => 'Alice Vet', 'signed_at' => now()->toIso8601String()]],
            'operator_name' => 'Jean Operator',
            'operator_signed_at' => now(),
            'stamp_acknowledged' => true,
        ]);

        $this->actingAs($this->processorUser)
            ->get(route('monthly-inspection-reports.index', [
                'apply' => '1',
                'month' => $lastMonth->format('Y-m'),
            ]))
            ->assertOk()
            ->assertSee($lastMonth->format('F Y'))
            ->assertSee('Submitted to RICA')
            ->assertSee('Open report');
    }

    public function test_month_filter_shows_unsubmitted_reports(): void
    {
        $month = now()->subMonth();

        $this->actingAs($this->processorUser)
            ->get(route('monthly-inspection-reports.index', [
                'apply' => '1',
                'month' => $month->format('Y-m'),
            ]))
            ->assertOk()
            ->assertSee($this->slaughterFacility->facility_name)
            ->assertSee('Not started')
            ->assertSee('Open report');

        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => $month->year,
            'period_month' => $month->month,
            'status' => RicaMonthlyInspectionReport::STATUS_DRAFT,
            'challenges' => 'Work in progress.',
        ]);

        $this->actingAs($this->processorUser)
            ->get(route('monthly-inspection-reports.index', [
                'apply' => '1',
                'month' => $month->format('Y-m'),
            ]))
            ->assertOk()
            ->assertSee('Draft')
            ->assertSee('Open report');
    }

    public function test_submitted_reports_search_filter(): void
    {
        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submitted_by_user_id' => $this->processorUser->id,
            'inspector_signatures' => [['name' => 'Alice Vet', 'signed_at' => now()->toIso8601String()]],
            'operator_name' => 'Jean Operator',
            'operator_signed_at' => now(),
            'stamp_acknowledged' => true,
        ]);

        $this->actingAs($this->processorUser)
            ->get(route('monthly-inspection-reports.index', [
                'apply' => '1',
                'all_periods' => '1',
                'search' => 'Nyagatare Modern',
            ]))
            ->assertOk()
            ->assertSee('Nyagatare Modern Slaughter House');

        $this->actingAs($this->processorUser)
            ->get(route('monthly-inspection-reports.index', [
                'apply' => '1',
                'all_periods' => '1',
                'search' => 'No Match Facility',
            ]))
            ->assertOk()
            ->assertSee('No reports have been submitted to RICA for the selected filters.');
    }

    public function test_submitted_monthly_report_cannot_be_edited(): void
    {
        RicaMonthlyInspectionReport::create([
            'facility_id' => $this->slaughterFacility->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submitted_by_user_id' => $this->processorUser->id,
            'inspector_signatures' => [['name' => 'Alice Vet', 'signed_at' => now()->toIso8601String()]],
            'operator_name' => 'Jean Operator',
            'operator_signed_at' => now(),
            'stamp_acknowledged' => true,
        ]);

        $this->actingAs($this->processorUser)
            ->post(route('monthly-inspection-reports.closure', $this->slaughterFacility), [
                'month' => now()->format('Y-m'),
                'challenges' => 'Attempted edit',
                'submit_to_rica' => '0',
            ])
            ->assertSessionHasErrors('status');
    }
}
