<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherComplianceOverride;
use App\Models\ButcherCustomer;
use App\Models\ButcherCutType;
use App\Models\ButcherDelivery;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherPriceRule;
use App\Models\ButcherProduct;
use App\Models\ButcherSale;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherCatalogService;
use App\Services\Butcher\ButcherCuttingService;
use App\Services\Butcher\ButcherProcurementService;
use App\Services\Butcher\ButcherReportService;
use App\Services\Butcher\ButcherSalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ButcherReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_is_accessible(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->butcher()->create([
            'user_id' => $user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-RPT-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        ButcherOutlet::query()->create([
            'business_id' => $business->id,
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->get(route('butcher.reports.index'))
            ->assertOk()
            ->assertSee(__('Reports'))
            ->assertSee(__('Receiving'))
            ->assertSee(__('Waste & adjustments'))
            ->assertSee(__('Stock counts'))
            ->assertSee(__('Batch traceability'))
            ->assertSee(__('Receivables'))
            ->assertSee(__('Compliance overrides'));
    }

    public function test_traceability_report_reconstructs_full_chain_for_sale(): void
    {
        Storage::fake('public');

        [$user, $business, $supplier, $outlet] = $this->seedButcherWorkspace('RDB-RPT-TRC');

        $delivery = app(ButcherProcurementService::class)->receiveDelivery($business, [
            'supplier_id' => $supplier->id,
            'outlet_id' => $outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 100,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $user);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $steak = $business->butcherCutTypes()->create([
            'name' => 'Steak',
            'meat_type' => ButcherCutType::MEAT_BEEF,
            'expected_yield_pct' => 90,
            'is_active' => true,
        ]);

        $product = app(ButcherCatalogService::class)->createProduct($business, [
            'name' => 'Fresh Steak',
            'cut_type_id' => $steak->id,
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 6000,
        ]);
        app(ButcherCatalogService::class)->setPriceRule($business, [
            'product_id' => $product->id,
            'customer_tier' => ButcherPriceRule::TIER_RETAIL,
            'price' => 6000,
            'valid_from' => now()->toDateString(),
        ]);
        app(ButcherCatalogService::class)->updateProduct($product->fresh(), [
            'name' => $product->name,
            'cut_type_id' => $product->cut_type_id,
            'meat_type' => $product->meat_type,
            'unit' => $product->unit,
            'default_price' => 6000,
            'is_active' => true,
        ]);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $business->id,
            'name' => 'Trace Hotel',
            'phone' => '+250788999000',
            'tier' => ButcherCustomer::TIER_RETAIL,
        ]);

        $cutting = app(ButcherCuttingService::class);
        $session = $cutting->openSession($business, [
            'outlet_id' => $outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 60,
        ]);
        $cutting->addCutOutput($session, ['cut_type_id' => $steak->id, 'weight_kg' => 55]);
        $cutting->closeSession($session->fresh(), $user);

        $sale = app(ButcherSalesService::class)->createSale($business, [
            'outlet_id' => $outlet->id,
            'customer_id' => $customer->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 60000,
            'items' => [['product_id' => $product->id, 'quantity_kg' => 10]],
        ], $user);

        $bySale = app(ButcherReportService::class)->saleTraceability($business, (int) $sale->id);
        $steps = collect($bySale['chain'])->pluck('step')->all();

        $this->assertSame('sale', $bySale['mode']);
        $this->assertContains('sale', $steps);
        $this->assertContains('cut_output', $steps);
        $this->assertContains('cutting_session', $steps);
        $this->assertContains('batch', $steps);
        $this->assertContains('delivery', $steps);
        $this->assertContains('supplier', $steps);
        $this->assertTrue(
            collect($bySale['chain'])->contains(fn (array $s) => ($s['reference'] ?? '') === $supplier->name)
        );
        $this->assertTrue(
            collect($bySale['chain'])->contains(fn (array $s) => ($s['reference'] ?? '') === $batch->batch_number)
        );

        $byBatch = app(ButcherReportService::class)->resolveTraceability($business, $batch->batch_number);
        $this->assertTrue($byBatch['found']);
        $this->assertSame('batch', $byBatch['mode']);
        $this->assertTrue(
            collect($byBatch['chain'])->contains(fn (array $s) => ($s['reference'] ?? '') === $sale->sale_number)
        );

        $this->actingAs($user)
            ->get(route('butcher.reports.traceability', ['q' => $sale->sale_number]))
            ->assertOk()
            ->assertSee($sale->sale_number)
            ->assertSee($supplier->name)
            ->assertSee($batch->batch_number)
            ->assertSee($customer->name);
    }

    public function test_receivables_and_overrides_linked_from_hub(): void
    {
        [$user, $business] = array_slice($this->seedButcherWorkspace('RDB-RPT-LNK'), 0, 2);

        ButcherComplianceOverride::query()->create([
            'business_id' => $business->id,
            'context_type' => ButcherComplianceOverride::CONTEXT_SALE,
            'context_id' => 1,
            'batch_id' => null,
            'cut_output_id' => null,
            'reason' => 'Test override for report',
            'issues' => [ButcherComplianceOverride::ISSUE_TEMPERATURE_BREACH],
            'overridden_by' => $user->id,
            'overridden_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('butcher.reports.index'))
            ->assertOk()
            ->assertSee(route('butcher.reports.traceability', absolute: false))
            ->assertSee(route('butcher.finance.receivables.index', absolute: false))
            ->assertSee(route('butcher.reports.compliance-overrides', absolute: false));

        $this->actingAs($user)
            ->get(route('butcher.finance.receivables.index'))
            ->assertOk()
            ->assertSee(__('Receivables'));

        $this->actingAs($user)
            ->get(route('butcher.reports.compliance-overrides'))
            ->assertOk()
            ->assertSee(__('Compliance overrides'))
            ->assertSee('Test override for report')
            ->assertSee($user->name);
    }

    public function test_traceability_and_overrides_are_tenant_isolated(): void
    {
        Storage::fake('public');

        [$userA, $businessA, $supplierA, $outletA] = $this->seedButcherWorkspace('RDB-RPT-A');
        [, $businessB] = array_slice($this->seedButcherWorkspace('RDB-RPT-B'), 0, 2);

        $delivery = app(ButcherProcurementService::class)->receiveDelivery($businessA, [
            'supplier_id' => $supplierA->id,
            'outlet_id' => $outletA->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 50,
            'unit_cost_per_kg' => 3000,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $userA);
        $batchA = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();

        ButcherComplianceOverride::query()->create([
            'business_id' => $businessA->id,
            'context_type' => ButcherComplianceOverride::CONTEXT_CUTTING_SESSION,
            'context_id' => 9,
            'batch_id' => $batchA->id,
            'reason' => 'Secret override A',
            'issues' => [ButcherComplianceOverride::ISSUE_EXPIRED],
            'overridden_by' => $userA->id,
            'overridden_at' => now(),
        ]);

        $userB = User::factory()->create();
        $businessB->update(['user_id' => $userB->id]);

        $resolved = app(ButcherReportService::class)->resolveTraceability($businessB, $batchA->batch_number);
        $this->assertFalse($resolved['found']);

        $this->actingAs($userB)
            ->get(route('butcher.reports.traceability', ['q' => $batchA->batch_number]))
            ->assertOk()
            ->assertSee(__('No batch or sale found'))
            ->assertDontSee($supplierA->name)
            ->assertDontSee(__('Chain'));

        $this->actingAs($userB)
            ->get(route('butcher.reports.compliance-overrides'))
            ->assertOk()
            ->assertDontSee('Secret override A');
    }

    /**
     * @return array{0: User, 1: Business, 2: ButcherSupplier, 3: ButcherOutlet}
     */
    private function seedButcherWorkspace(string $registration): array
    {
        $user = User::factory()->create();
        $business = Business::factory()->butcher()->create([
            'user_id' => $user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => $registration,
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        $supplier = ButcherSupplier::query()->create([
            'business_id' => $business->id,
            'name' => 'Supplier '.$registration,
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
            'is_active' => true,
        ]);

        $outlet = ButcherOutlet::query()->create([
            'business_id' => $business->id,
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);

        return [$user, $business, $supplier, $outlet];
    }
}
