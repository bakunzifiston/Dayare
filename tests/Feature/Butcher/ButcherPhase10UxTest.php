<?php

namespace Tests\Feature\Butcher;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherPermit;
use App\Models\ButcherSale;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherDashboardService;
use App\Services\Butcher\ButcherOnboardingService;
use App\Services\Butcher\ButcherProcurementService;
use App\Services\Butcher\ButcherReportService;
use App\Services\Butcher\ButcherSalesService;
use App\Services\Butcher\ButcherStorageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ButcherPhase10UxTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_payload_relocates_figures_without_changing_numbers(): void
    {
        [$user, $business] = $this->seedButcherWithCompletedOnboarding('RDB-P10-DASH', 'Phase10 Butchery');

        $payload = app(ButcherDashboardService::class)->build($user);

        $this->assertNotNull($payload['today']);
        $this->assertNotNull($payload['overview']);

        // Legacy keys still present and match relocated values
        $this->assertSame(
            $payload['today_at_glance']['sales_count']['value'],
            $payload['today']['sales_count']['value']
        );
        $this->assertSame(
            $payload['today_at_glance']['revenue']['value'],
            $payload['today']['revenue']['value']
        );
        $this->assertSame(
            $payload['finance']['credit_outstanding']['value'],
            $payload['today']['credit_outstanding']['value']
        );
        $this->assertSame(
            $payload['finance']['revenue_mtd']['value'],
            $payload['overview']['finance']['revenue_mtd']['value']
        );
        $this->assertSame(
            $payload['finance']['cogs']['value'],
            $payload['overview']['finance']['cogs']['value']
        );
        $this->assertSame(
            $payload['finance']['gross_margin_pct']['value'],
            $payload['overview']['finance']['gross_margin_pct']['value']
        );
        $this->assertSame(
            $payload['sales']['open_orders']['value'],
            $payload['today']['open_orders']['value']
        );
        $this->assertSame(
            $payload['compliance_kpis']['permits_expiring']['value'],
            $payload['overview']['compliance']['permits_expiring']['value']
        );
        $this->assertSame(
            $payload['compliance_kpis']['audit_readiness']['value'],
            $payload['overview']['compliance']['audit_readiness']['value']
        );
        $this->assertSame(
            $payload['compliance_kpis']['hygiene_log']['value'],
            $payload['today']['hygiene_log']['value']
        );

        $this->actingAs($user)
            ->get(route('butcher.dashboard'))
            ->assertOk()
            ->assertSee(__('Today'))
            ->assertSee(__('Open orders'))
            ->assertSee(__('Expiring soon'));

        $this->actingAs($user)
            ->get(route('butcher.dashboard', ['section' => 'overview']))
            ->assertOk()
            ->assertSee(__('Finance (month to date)'))
            ->assertSee(__('Yield & waste (30 days)'));
    }

    public function test_outlet_filter_scopes_inventory_sales_and_reports(): void
    {
        Storage::fake('public');

        [$user, $business] = $this->seedButcherWithCompletedOnboarding('RDB-P10-OUT', 'Multi Outlet');
        $main = $business->butcherOutlets()->firstOrFail();
        $second = ButcherOutlet::query()->create([
            'business_id' => $business->id,
            'name' => 'Branch B',
            'district' => 'Kigali',
            'phone' => '+250788222222',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);
        $supplier = $business->butcherSuppliers()->firstOrFail();

        $procurement = app(ButcherProcurementService::class);
        $deliveryA = $procurement->receiveDelivery($business, [
            'supplier_id' => $supplier->id,
            'outlet_id' => $main->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 40,
            'unit_cost_per_kg' => 3000,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $user);
        $deliveryB = $procurement->receiveDelivery($business, [
            'supplier_id' => $supplier->id,
            'outlet_id' => $second->id,
            'meat_type' => ButcherDelivery::MEAT_GOAT,
            'received_weight_kg' => 25,
            'unit_cost_per_kg' => 4000,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $user);

        $batchA = ButcherInventoryBatch::query()->where('delivery_id', $deliveryA->id)->firstOrFail();
        $batchB = ButcherInventoryBatch::query()->where('delivery_id', $deliveryB->id)->firstOrFail();

        ButcherSale::query()->create([
            'business_id' => $business->id,
            'outlet_id' => $main->id,
            'sale_number' => 'SALE-MAIN-001',
            'sale_date' => now()->toDateString(),
            'subtotal' => 10000,
            'total_amount' => 10000,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 10000,
            'status' => ButcherSale::STATUS_COMPLETED,
            'sold_by' => $user->id,
        ]);
        ButcherSale::query()->create([
            'business_id' => $business->id,
            'outlet_id' => $second->id,
            'sale_number' => 'SALE-BRANCH-001',
            'sale_date' => now()->toDateString(),
            'subtotal' => 8000,
            'total_amount' => 8000,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 8000,
            'status' => ButcherSale::STATUS_COMPLETED,
            'sold_by' => $user->id,
        ]);

        $allSummary = app(ButcherStorageService::class)->getStorageSummary($business);
        $mainSummary = app(ButcherStorageService::class)->getStorageSummary($business, (int) $main->id);
        $this->assertSame(2, $allSummary['batches_in_storage']);
        $this->assertSame(1, $mainSummary['batches_in_storage']);
        $this->assertEqualsWithDelta(40.0, $mainSummary['kg_in_storage'], 0.01);

        $allSales = app(ButcherSalesService::class)->getDailySalesSummary($business, Carbon::today());
        $mainSales = app(ButcherSalesService::class)->getDailySalesSummary($business, Carbon::today(), (int) $main->id);
        $this->assertSame(2, $allSales['sales_count']);
        $this->assertSame(1, $mainSales['sales_count']);
        $this->assertEqualsWithDelta(10000.0, $mainSales['gross_total'], 0.01);

        $allHub = app(ButcherReportService::class)->buildHub($business, now()->startOfMonth(), now()->endOfDay());
        $mainHub = app(ButcherReportService::class)->buildHub($business, now()->startOfMonth(), now()->endOfDay(), (int) $main->id);
        $this->assertSame($allHub['kpis']['batches'], 2);
        $this->assertSame($mainHub['kpis']['batches'], 1);
        $this->assertSame($mainHub['kpis']['sales_count'], 1);

        $this->actingAs($user)
            ->get(route('butcher.inventory.batches.index', ['outlet_id' => $second->id]))
            ->assertOk()
            ->assertSee($batchB->batch_number)
            ->assertDontSee($batchA->batch_number);

        $this->actingAs($user)
            ->get(route('butcher.sales.index', ['outlet_id' => $main->id]))
            ->assertOk()
            ->assertSee('SALE-MAIN-001')
            ->assertDontSee('SALE-BRANCH-001');

        // Unfiltered (= all outlets) still returns both — regression for default behavior
        $this->actingAs($user)
            ->get(route('butcher.sales.index'))
            ->assertOk()
            ->assertSee('SALE-MAIN-001')
            ->assertSee('SALE-BRANCH-001');
    }

    /**
     * @return array{0: User, 1: Business}
     */
    private function seedButcherWithCompletedOnboarding(string $reg, string $name): array
    {
        $this->seedRwandaDistrict('Kigali');

        $user = User::factory()->create();
        $business = Business::factory()->butcher()->create([
            'user_id' => $user->id,
            'business_name' => $name,
            'status' => Business::STATUS_PENDING,
            'registration_number' => 'PENDING-'.$reg,
            'tax_id' => null,
            'contact_phone' => '0000000000',
        ]);

        $onboarding = app(ButcherOnboardingService::class);

        $onboarding->createBusinessProfile([
            'business_name' => $name,
            'butchery_type' => Business::BUTCHERY_TYPE_RETAIL,
            'rdb_registration_number' => $reg,
            'tin_number' => '1234567890',
            'phone' => '+250788123456',
            'district' => 'Kigali',
        ], $user);

        $onboarding->addOutlet($business->fresh(), [
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'is_primary' => true,
        ]);

        $onboarding->uploadPermit($business->fresh(), [
            'permit_type' => ButcherPermit::TYPE_RICA,
            'permit_number' => 'RICA-'.$reg,
            'issued_by' => 'RICA',
            'issue_date' => now()->subMonths(2)->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
        ], null);

        $onboarding->createSupplier($business->fresh(), [
            'name' => 'Supplier',
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
        ]);

        return [$user, $business->fresh()];
    }

    private function seedRwandaDistrict(string $name): void
    {
        AdministrativeDivision::query()->create([
            'parent_id' => null,
            'name' => 'Rwanda',
            'type' => AdministrativeDivision::TYPE_COUNTRY,
        ]);

        AdministrativeDivision::query()->create([
            'parent_id' => null,
            'name' => $name,
            'type' => AdministrativeDivision::TYPE_DISTRICT,
        ]);
    }
}
