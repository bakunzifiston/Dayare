<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherHygieneLog;
use App\Models\ButcherOutlet;
use App\Models\ButcherPermit;
use App\Models\ButcherStaffHealthRecord;
use App\Models\User;
use App\Services\Butcher\ButcherComplianceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ButcherComplianceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private ButcherOutlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-CMP-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        $this->outlet = ButcherOutlet::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);
    }

    public function test_compliance_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.compliance.index'))
            ->assertOk()
            ->assertSee(__('Compliance & hygiene'));
    }

    public function test_hygiene_log_enforces_one_per_outlet_per_day(): void
    {
        $service = app(ButcherComplianceService::class);

        $service->logHygiene($this->business, [
            'outlet_id' => $this->outlet->id,
            'log_date' => now()->toDateString(),
            'checklist' => ['floor_cleaned' => true, 'knives_sanitized' => true],
        ], $this->user);

        $this->expectException(ValidationException::class);

        $service->logHygiene($this->business, [
            'outlet_id' => $this->outlet->id,
            'log_date' => now()->toDateString(),
            'checklist' => ['floor_cleaned' => true],
        ], $this->user);
    }

    public function test_hygiene_status_is_derived_from_checklist(): void
    {
        $log = app(ButcherComplianceService::class)->logHygiene($this->business, [
            'outlet_id' => $this->outlet->id,
            'checklist' => array_fill_keys(array_keys(ButcherHygieneLog::DEFAULT_CHECKLIST), true),
        ], $this->user);

        $this->assertSame(ButcherHygieneLog::STATUS_PASS, $log->status);

        $partial = app(ButcherComplianceService::class)->logHygiene($this->business, [
            'outlet_id' => ButcherOutlet::query()->create([
                'business_id' => $this->business->id,
                'name' => 'Branch',
                'district' => 'Kigali',
                'phone' => '+250788222222',
                'status' => ButcherOutlet::STATUS_ACTIVE,
            ])->id,
            'checklist' => ['floor_cleaned' => true],
        ], $this->user);

        $this->assertSame(ButcherHygieneLog::STATUS_PARTIAL, $partial->status);
    }

    public function test_compliance_alerts_detect_expiring_permits_and_health_cards(): void
    {
        ButcherPermit::query()->create([
            'business_id' => $this->business->id,
            'permit_type' => ButcherPermit::TYPE_RFA_PERMIT,
            'permit_number' => 'RFA-001',
            'issued_by' => 'RFA',
            'issue_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'status' => ButcherPermit::STATUS_VALID,
        ]);

        app(ButcherComplianceService::class)->upsertStaffHealth($this->business, [
            'user_id' => $this->user->id,
            'medical_card_number' => 'HC-001',
            'issued_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addDays(15)->toDateString(),
            'health_status' => ButcherStaffHealthRecord::STATUS_FIT,
        ]);

        $alerts = app(ButcherComplianceService::class)->getComplianceAlerts($this->business);

        $this->assertGreaterThan(0, $alerts['expiring_permit_count']);
        $this->assertGreaterThan(0, $alerts['expiring_health_count']);
        $this->assertGreaterThan(0, $alerts['missing_hygiene_count']);
    }

    public function test_audit_report_export_generates_csv(): void
    {
        app(ButcherComplianceService::class)->logHygiene($this->business, [
            'outlet_id' => $this->outlet->id,
            'checklist' => ['floor_cleaned' => true],
        ], $this->user);

        $from = Carbon::now()->subDays(7);
        $to = Carbon::now();

        $path = app(ButcherComplianceService::class)->exportAuditReport($this->business, $from, $to);

        Storage::disk('public')->assertExists($path);
    }

    public function test_compliance_flow_via_http(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.compliance.hygiene.store'), [
                'outlet_id' => $this->outlet->id,
                'checklist' => [
                    'floor_cleaned' => '1',
                    'knives_sanitized' => '1',
                    'cold_room_checked' => '1',
                    'waste_disposed' => '1',
                    'surfaces_wiped' => '1',
                    'staff_ppe_worn' => '1',
                ],
            ])
            ->assertRedirect();

        $this->actingAs($this->user)
            ->post(route('butcher.compliance.sanitation.store'), [
                'outlet_id' => $this->outlet->id,
                'equipment_name' => 'Band saw',
                'cleaning_type' => 'daily_clean',
                'performed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('butcher.compliance.sanitation.index'));

        $this->actingAs($this->user)
            ->post(route('butcher.compliance.health.store'), [
                'user_id' => $this->user->id,
                'medical_card_number' => 'MED-123',
                'issued_date' => now()->subMonths(6)->toDateString(),
                'expiry_date' => now()->addYear()->toDateString(),
                'health_status' => ButcherStaffHealthRecord::STATUS_FIT,
            ])
            ->assertRedirect(route('butcher.compliance.health.index'));

        $this->actingAs($this->user)
            ->get(route('butcher.compliance.report.export', [
                'from' => now()->subDays(30)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk();
    }
}
