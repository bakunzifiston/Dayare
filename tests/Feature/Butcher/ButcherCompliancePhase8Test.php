<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\ButcherComplianceOverride;
use App\Models\ButcherCustomer;
use App\Models\ButcherCutType;
use App\Models\ButcherDelivery;
use App\Models\ButcherHygieneLog;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherPriceRule;
use App\Models\ButcherProduct;
use App\Models\ButcherSale;
use App\Models\ButcherSupplier;
use App\Models\ButcherTemperatureLog;
use App\Models\User;
use App\Services\Butcher\BatchSafetyGate;
use App\Services\Butcher\ButcherCatalogService;
use App\Services\Butcher\ButcherComplianceService;
use App\Services\Butcher\ButcherCuttingService;
use App\Services\Butcher\ButcherProcurementService;
use App\Services\Butcher\ButcherSalesService;
use App\Services\Butcher\ButcherStorageService;
use App\Services\Butcher\InventoryConsumptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ButcherCompliancePhase8Test extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Business $business;

    private ButcherOutlet $outlet;

    private ButcherSupplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->owner = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->owner->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-CMP8-001',
            'butcher_fresh_max_temp_c' => 4,
            'butcher_frozen_max_temp_c' => -18,
            'butcher_batch_shelf_life_days' => 3,
        ]);
        BusinessUser::query()->updateOrCreate(
            ['business_id' => $this->business->id, 'user_id' => $this->owner->id],
            ['role' => BusinessUser::ROLE_BUTCHER_OWNER]
        );

        $this->supplier = ButcherSupplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Supplier',
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
            'is_active' => true,
        ]);

        $this->outlet = ButcherOutlet::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);
    }

    public function test_temperature_breach_flags_matching_batches(): void
    {
        $batch = $this->createBatch(['storage_location' => 'Fridge A']);

        $this->actingAs($this->owner)
            ->post(route('butcher.inventory.temperatures.store'), [
                'outlet_id' => $this->outlet->id,
                'storage_location' => 'Fridge A',
                'storage_type' => ButcherTemperatureLog::TYPE_FRESH,
                'temperature_celsius' => 8,
            ])
            ->assertRedirect();

        $batch->refresh();
        $this->assertTrue($batch->temperature_breach);
        $this->assertNotNull($batch->temperature_breach_at);
    }

    public function test_processor_cannot_cut_from_breached_batch_without_override(): void
    {
        $batch = $this->createBatch(['storage_location' => 'Fridge A']);
        app(ButcherStorageService::class)->logTemperature($this->business, [
            'outlet_id' => $this->outlet->id,
            'storage_location' => 'Fridge A',
            'storage_type' => ButcherTemperatureLog::TYPE_FRESH,
            'temperature_celsius' => 9,
        ], $this->owner);

        $processor = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $processor->id,
            'role' => BusinessUser::ROLE_BUTCHER_PROCESSOR,
        ]);

        $this->expectException(ValidationException::class);
        app(ButcherCuttingService::class)->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 10,
        ], $processor);
    }

    public function test_manager_can_override_breached_batch_and_override_is_logged(): void
    {
        $batch = $this->createBatch(['storage_location' => 'Fridge A']);
        app(ButcherStorageService::class)->logTemperature($this->business, [
            'outlet_id' => $this->outlet->id,
            'storage_location' => 'Fridge A',
            'storage_type' => ButcherTemperatureLog::TYPE_FRESH,
            'temperature_celsius' => 9,
        ], $this->owner);

        $manager = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $manager->id,
            'role' => BusinessUser::ROLE_BUTCHER_MANAGER,
        ]);

        $session = app(ButcherCuttingService::class)->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 10,
            'safety_override_reason' => 'Manager approved after visual inspection',
        ], $manager);

        $this->assertNotNull($session->id);
        $override = ButcherComplianceOverride::query()->first();
        $this->assertNotNull($override);
        $this->assertSame($batch->id, (int) $override->batch_id);
        $this->assertSame($manager->id, (int) $override->overridden_by);
        $this->assertStringContainsString('visual inspection', $override->reason);
        $this->assertContains(ButcherComplianceOverride::ISSUE_TEMPERATURE_BREACH, $override->issues);
    }

    public function test_cashier_cannot_sell_from_breached_cut_output(): void
    {
        [$product, $output, $batch] = $this->seedProductOutput(15);
        $batch->update([
            'temperature_breach' => true,
            'temperature_breach_at' => now(),
            'storage_location' => 'Fridge A',
        ]);

        $cashier = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $cashier->id,
            'role' => BusinessUser::ROLE_BUTCHER_CASHIER,
        ]);

        $this->expectException(ValidationException::class);
        app(ButcherSalesService::class)->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 25000,
            'items' => [['product_id' => $product->id, 'quantity_kg' => 2]],
        ], $cashier);
    }

    public function test_owner_can_sell_from_breached_stock_with_override(): void
    {
        [$product, $output, $batch] = $this->seedProductOutput(15);
        $batch->update([
            'temperature_breach' => true,
            'temperature_breach_at' => now(),
        ]);

        $sale = app(ButcherSalesService::class)->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 25000,
            'safety_override_reason' => 'Owner override for distressed stock clearance',
            'items' => [['product_id' => $product->id, 'quantity_kg' => 2]],
        ], $this->owner);

        $this->assertSame(ButcherSale::STATUS_COMPLETED, $sale->status);
        $this->assertTrue(
            ButcherComplianceOverride::query()
                ->where('context_type', ButcherComplianceOverride::CONTEXT_SALE)
                ->where('batch_id', $batch->id)
                ->exists()
        );
    }

    public function test_hygiene_banner_appears_after_cutoff_without_blocking(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 11:00:00'));
        config(['butcher.hygiene_log_cutoff' => '10:00']);

        $banner = app(ButcherComplianceService::class)
            ->hygieneMissingBanner($this->business, $this->outlet->id);

        $this->assertTrue($banner['show']);
        $this->assertNotNull($banner['message']);

        ButcherHygieneLog::query()->create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'log_date' => now()->toDateString(),
            'checklist' => ['floors' => true],
            'signed_by' => $this->owner->id,
            'status' => ButcherHygieneLog::STATUS_PASS,
        ]);

        $bannerAfter = app(ButcherComplianceService::class)
            ->hygieneMissingBanner($this->business, $this->outlet->id);
        $this->assertFalse($bannerAfter['show']);

        Carbon::setTestNow();
    }

    public function test_hygiene_banner_hidden_before_cutoff(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 09:00:00'));
        config(['butcher.hygiene_log_cutoff' => '10:00']);

        $banner = app(ButcherComplianceService::class)
            ->hygieneMissingBanner($this->business, $this->outlet->id);

        $this->assertFalse($banner['show']);
        Carbon::setTestNow();
    }

    public function test_precommit_recheck_catches_concurrent_temperature_breach(): void
    {
        [$product, $output, $batch] = $this->seedProductOutput(12);

        $this->expectException(ValidationException::class);

        DB::transaction(function () use ($product, $batch) {
            ButcherInventoryBatch::query()->whereKey($batch->id)->lockForUpdate()->first();

            // Concurrent breach lands after the gate would have initially passed.
            $batch->update([
                'temperature_breach' => true,
                'temperature_breach_at' => now(),
            ]);

            app(InventoryConsumptionService::class)->consumeCutOutputs(
                businessId: $this->business->id,
                cutTypeId: (int) $product->cut_type_id,
                quantityKg: 2,
                outletId: $this->outlet->id,
                actor: $this->owner,
                referenceType: ButcherSale::class,
                referenceId: 1,
            );
        });
    }

    public function test_expired_batch_requires_override_like_breach(): void
    {
        $batch = $this->createBatch();
        $batch->update(['best_before_date' => now()->subDay()->toDateString()]);

        $this->expectException(ValidationException::class);
        app(ButcherCuttingService::class)->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 5,
        ], $this->owner);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBatch(array $overrides = []): ButcherInventoryBatch
    {
        $delivery = app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 48.25,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
            'storage_location' => $overrides['storage_location'] ?? 'Fridge A',
        ], $this->owner);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
        if ($overrides !== []) {
            $batch->update($overrides);
        }

        return $batch->fresh();
    }

    /**
     * @return array{0: ButcherProduct, 1: \App\Models\ButcherCutOutput, 2: ButcherInventoryBatch}
     */
    private function seedProductOutput(float $stockKg): array
    {
        $batch = $this->createBatch(['storage_location' => 'Fridge A']);
        $cutType = $this->business->butcherCutTypes()->create([
            'name' => 'Sirloin',
            'meat_type' => ButcherCutType::MEAT_BEEF,
            'expected_yield_pct' => 85,
            'is_active' => true,
        ]);

        $product = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Fresh Sirloin',
            'cut_type_id' => $cutType->id,
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 5000,
        ]);
        app(ButcherCatalogService::class)->setPriceRule($this->business, [
            'product_id' => $product->id,
            'customer_tier' => ButcherPriceRule::TIER_RETAIL,
            'price' => 5000,
            'valid_from' => now()->toDateString(),
        ]);
        app(ButcherCatalogService::class)->updateProduct($product->fresh(), [
            'name' => $product->name,
            'cut_type_id' => $product->cut_type_id,
            'meat_type' => $product->meat_type,
            'unit' => $product->unit,
            'default_price' => 5000,
            'is_active' => true,
        ]);

        $cutting = app(ButcherCuttingService::class);
        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => $stockKg + 5,
        ], $this->owner);
        $output = $cutting->addCutOutput($session, [
            'cut_type_id' => $cutType->id,
            'weight_kg' => $stockKg,
        ]);
        $cutting->closeSession($session->fresh(), $this->owner);

        return [$product->fresh(), $output->fresh(), $batch->fresh()];
    }
}
