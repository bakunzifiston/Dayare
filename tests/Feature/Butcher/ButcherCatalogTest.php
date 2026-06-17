<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherCutType;
use App\Models\ButcherDelivery;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherPriceRule;
use App\Models\ButcherProduct;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherCatalogService;
use App\Services\Butcher\ButcherCuttingService;
use App\Services\Butcher\ButcherProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private ButcherOutlet $outlet;

    private ButcherSupplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-CAT-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

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

    public function test_catalog_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.catalog.index'))
            ->assertOk()
            ->assertSee(__('Product catalog'));
    }

    public function test_create_product_calculates_margin(): void
    {
        $product = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Fresh T-Bone Steak',
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 5000,
        ]);

        $this->assertSame('Fresh T-Bone Steak', $product->name);
        $this->assertEqualsWithDelta(100, (float) $product->margin_pct, 0.01);
    }

    public function test_recalculate_avg_cost_from_cut_outputs(): void
    {
        $cutType = $this->createCutType();
        $product = $this->createProduct($cutType);
        $this->closeCuttingSession($cutType, 10, 4000);

        app(ButcherCatalogService::class)->recalculateAvgCost($product->fresh());

        $product->refresh();
        $this->assertGreaterThan(0, (float) $product->avg_cost_per_kg);
        $this->assertLessThan(100, (float) $product->margin_pct);
    }

    public function test_session_close_recalculates_linked_product_cost(): void
    {
        $cutType = $this->createCutType();
        $product = $this->createProduct($cutType);

        $this->closeCuttingSession($cutType, 12, 3500);

        $product->refresh();
        $this->assertGreaterThan(0, (float) $product->avg_cost_per_kg);
    }

    public function test_resolve_price_priority(): void
    {
        $product = $this->createProduct($this->createCutType(), 6000);
        $catalog = app(ButcherCatalogService::class);

        $catalog->setPriceRule($this->business, [
            'product_id' => $product->id,
            'price' => 5500,
            'valid_from' => now()->subDay()->toDateString(),
        ]);

        $catalog->setPriceRule($this->business, [
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'price' => 5200,
            'valid_from' => now()->subDay()->toDateString(),
        ]);

        $catalog->setPriceRule($this->business, [
            'product_id' => $product->id,
            'customer_tier' => ButcherPriceRule::TIER_WHOLESALE,
            'price' => 4800,
            'valid_from' => now()->subDay()->toDateString(),
        ]);

        $catalog->setPriceRule($this->business, [
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'customer_tier' => ButcherPriceRule::TIER_WHOLESALE,
            'price' => 4500,
            'valid_from' => now()->subDay()->toDateString(),
        ]);

        $this->assertEqualsWithDelta(6000, $catalog->resolvePrice($product), 0.01);
        $this->assertEqualsWithDelta(5200, $catalog->resolvePrice($product, $this->outlet->id), 0.01);
        $this->assertEqualsWithDelta(4800, $catalog->resolvePrice($product, null, ButcherPriceRule::TIER_WHOLESALE), 0.01);
        $this->assertEqualsWithDelta(4500, $catalog->resolvePrice($product, $this->outlet->id, ButcherPriceRule::TIER_WHOLESALE), 0.01);
    }

    public function test_product_and_price_rule_flow_via_http(): void
    {
        $cutType = $this->createCutType();

        $this->actingAs($this->user)
            ->post(route('butcher.catalog.products.store'), [
                'name' => 'Minced Beef 500g',
                'cut_type_id' => $cutType->id,
                'meat_type' => ButcherProduct::MEAT_BEEF,
                'unit' => ButcherProduct::UNIT_PER_PACK,
                'default_price' => 3500,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $product = $this->business->butcherProducts()->first();
        $this->assertNotNull($product);

        $this->actingAs($this->user)
            ->post(route('butcher.catalog.pricing.store'), [
                'product_id' => $product->id,
                'outlet_id' => $this->outlet->id,
                'customer_tier' => ButcherPriceRule::TIER_RETAIL,
                'price' => 3200,
                'valid_from' => now()->toDateString(),
                'valid_until' => now()->addDays(7)->toDateString(),
            ])
            ->assertRedirect(route('butcher.catalog.pricing.index'));

        $rule = $this->business->butcherPriceRules()->first();
        $this->assertNotNull($rule);

        $this->actingAs($this->user)
            ->delete(route('butcher.catalog.pricing.destroy', $rule))
            ->assertRedirect(route('butcher.catalog.pricing.index'));

        $this->assertDatabaseMissing('butcher_price_rules', ['id' => $rule->id]);
    }

    private function createCutType(): ButcherCutType
    {
        return $this->business->butcherCutTypes()->create([
            'name' => 'T-Bone',
            'meat_type' => ButcherCutType::MEAT_BEEF,
            'expected_yield_pct' => 85,
            'is_active' => true,
        ]);
    }

    private function createProduct(ButcherCutType $cutType, float $price = 5000): ButcherProduct
    {
        return app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Fresh T-Bone Steak',
            'cut_type_id' => $cutType->id,
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => $price,
        ]);
    }

    private function closeCuttingSession(ButcherCutType $cutType, float $outputWeight, float $batchUnitCost): void
    {
        $delivery = app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 48.25,
            'unit_cost_per_kg' => $batchUnitCost,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $this->user);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $cutting = app(ButcherCuttingService::class);

        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 20,
        ]);

        $cutting->addCutOutput($session, [
            'cut_type_id' => $cutType->id,
            'weight_kg' => $outputWeight,
        ]);

        $cutting->closeSession($session->fresh());
    }
}
