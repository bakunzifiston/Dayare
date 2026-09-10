<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\BusinessUser;
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
use App\Services\Butcher\ButcherSalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Business $business;

    private ButcherOutlet $outlet;

    private ButcherCutType $cutType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->owner->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-CAT-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        BusinessUser::query()->updateOrCreate(
            ['business_id' => $this->business->id, 'user_id' => $this->owner->id],
            ['role' => BusinessUser::ROLE_BUTCHER_OWNER]
        );

        $this->outlet = ButcherOutlet::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);

        $this->cutType = $this->business->butcherCutTypes()->create([
            'name' => 'Sirloin',
            'meat_type' => ButcherCutType::MEAT_BEEF,
            'expected_yield_pct' => 85,
            'is_active' => true,
        ]);
    }

    public function test_owner_can_create_and_edit_product_via_ui(): void
    {
        $this->actingAs($this->owner)
            ->post(route('butcher.catalog.products.store'), [
                'name' => 'Fresh Sirloin',
                'cut_type_id' => $this->cutType->id,
                'meat_type' => ButcherProduct::MEAT_BEEF,
                'unit' => ButcherProduct::UNIT_PER_KG,
                'default_price' => 5000,
            ])
            ->assertRedirect();

        $product = ButcherProduct::query()->where('business_id', $this->business->id)->first();
        $this->assertNotNull($product);
        $this->assertSame('Fresh Sirloin', $product->name);
        $this->assertFalse($product->is_active);
        $this->assertSame($this->cutType->id, (int) $product->cut_type_id);

        $this->actingAs($this->owner)
            ->put(route('butcher.catalog.products.update', $product), [
                'name' => 'Premium Sirloin',
                'cut_type_id' => $this->cutType->id,
                'meat_type' => ButcherProduct::MEAT_BEEF,
                'unit' => ButcherProduct::UNIT_PER_KG,
                'default_price' => 5500,
                'is_active' => '0',
            ])
            ->assertRedirect(route('butcher.catalog.products.show', $product));

        $this->assertSame('Premium Sirloin', $product->fresh()->name);
        $this->assertEqualsWithDelta(5500, (float) $product->fresh()->default_price, 0.01);
    }

    public function test_activation_blocked_without_retail_price_rule(): void
    {
        $product = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'No Price Yet',
            'cut_type_id' => $this->cutType->id,
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 4000,
        ]);

        $this->actingAs($this->owner)
            ->from(route('butcher.catalog.products.edit', $product))
            ->put(route('butcher.catalog.products.update', $product), [
                'name' => $product->name,
                'cut_type_id' => $product->cut_type_id,
                'meat_type' => $product->meat_type,
                'unit' => $product->unit,
                'default_price' => $product->default_price,
                'is_active' => '1',
            ])
            ->assertRedirect(route('butcher.catalog.products.edit', $product))
            ->assertSessionHasErrors('is_active');

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_price_rules_can_be_created_and_edited_per_tier(): void
    {
        $product = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Priced Cut',
            'cut_type_id' => $this->cutType->id,
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 5000,
        ]);

        foreach ([
            ButcherPriceRule::TIER_RETAIL => 5000,
            ButcherPriceRule::TIER_WHOLESALE => 4500,
            ButcherPriceRule::TIER_LOYALTY => 4800,
        ] as $tier => $price) {
            $this->actingAs($this->owner)
                ->post(route('butcher.catalog.price-rules.store', $product), [
                    'product_id' => $product->id,
                    'customer_tier' => $tier,
                    'price' => $price,
                    'valid_from' => now()->toDateString(),
                    'is_active' => '1',
                ])
                ->assertRedirect(route('butcher.catalog.products.show', $product));
        }

        $this->assertSame(3, $product->priceRules()->count());

        $retail = $product->priceRules()->where('customer_tier', ButcherPriceRule::TIER_RETAIL)->firstOrFail();

        $this->actingAs($this->owner)
            ->put(route('butcher.catalog.price-rules.update', [$product, $retail]), [
                'customer_tier' => ButcherPriceRule::TIER_RETAIL,
                'price' => 5200,
                'valid_from' => now()->toDateString(),
                'is_active' => '1',
            ])
            ->assertRedirect(route('butcher.catalog.products.show', $product));

        $this->assertEqualsWithDelta(5200, (float) $retail->fresh()->price, 0.01);
        $this->assertEqualsWithDelta(5200, (float) $product->fresh()->default_price, 0.01);

        $this->actingAs($this->owner)
            ->put(route('butcher.catalog.products.update', $product), [
                'name' => $product->name,
                'cut_type_id' => $product->cut_type_id,
                'meat_type' => $product->meat_type,
                'unit' => $product->unit,
                'default_price' => $product->fresh()->default_price,
                'is_active' => '1',
            ])
            ->assertRedirect(route('butcher.catalog.products.show', $product));

        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_pos_resolves_tier_price_for_ui_created_product(): void
    {
        $this->actingAs($this->owner)
            ->post(route('butcher.catalog.products.store'), [
                'name' => 'UI Sirloin',
                'cut_type_id' => $this->cutType->id,
                'meat_type' => ButcherProduct::MEAT_BEEF,
                'unit' => ButcherProduct::UNIT_PER_KG,
                'default_price' => 5000,
            ])
            ->assertRedirect();

        $product = ButcherProduct::query()->where('name', 'UI Sirloin')->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('butcher.catalog.price-rules.store', $product), [
                'product_id' => $product->id,
                'customer_tier' => ButcherPriceRule::TIER_RETAIL,
                'price' => 5000,
                'valid_from' => now()->toDateString(),
                'is_active' => '1',
            ]);

        $this->actingAs($this->owner)
            ->post(route('butcher.catalog.price-rules.store', $product), [
                'product_id' => $product->id,
                'customer_tier' => ButcherPriceRule::TIER_WHOLESALE,
                'price' => 4200,
                'valid_from' => now()->toDateString(),
                'is_active' => '1',
            ]);

        $this->actingAs($this->owner)
            ->put(route('butcher.catalog.products.update', $product), [
                'name' => $product->name,
                'cut_type_id' => $product->cut_type_id,
                'meat_type' => $product->meat_type,
                'unit' => $product->unit,
                'default_price' => 5000,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->seedCutStock($product, 20);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Hotel Buyer',
            'phone' => '+250788999999',
            'tier' => ButcherCustomer::TIER_WHOLESALE,
        ]);

        $sale = app(ButcherSalesService::class)->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'customer_id' => $customer->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 42000,
            'items' => [
                ['product_id' => $product->id, 'quantity_kg' => 10],
            ],
        ], $this->owner);

        $item = $sale->items->first();
        $this->assertNotNull($item);
        $this->assertEqualsWithDelta(4200, (float) $item->unit_price, 0.01);
        $this->assertEqualsWithDelta(42000, (float) $sale->total_amount, 0.01);
    }

    public function test_tenant_isolation_for_products_and_price_rules(): void
    {
        $product = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Business A Product',
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 3000,
        ]);

        $otherOwner = User::factory()->create();
        $otherBusiness = Business::factory()->butcher()->create([
            'user_id' => $otherOwner->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-CAT-OTHER',
        ]);
        BusinessUser::query()->updateOrCreate(
            ['business_id' => $otherBusiness->id, 'user_id' => $otherOwner->id],
            ['role' => BusinessUser::ROLE_BUTCHER_OWNER]
        );

        $this->actingAs($otherOwner)
            ->get(route('butcher.catalog.products.show', $product))
            ->assertNotFound();

        $this->actingAs($otherOwner)
            ->put(route('butcher.catalog.products.update', $product), [
                'name' => 'Hijacked',
                'meat_type' => ButcherProduct::MEAT_BEEF,
                'unit' => ButcherProduct::UNIT_PER_KG,
                'default_price' => 1,
                'is_active' => '0',
            ])
            ->assertNotFound();

        $this->actingAs($otherOwner)
            ->post(route('butcher.catalog.price-rules.store', $product), [
                'product_id' => $product->id,
                'customer_tier' => ButcherPriceRule::TIER_RETAIL,
                'price' => 1,
                'valid_from' => now()->toDateString(),
            ])
            ->assertNotFound();

        $this->assertSame('Business A Product', $product->fresh()->name);
        $this->assertSame(0, $product->priceRules()->count());
    }

    public function test_cashier_can_view_catalog_but_cannot_manage(): void
    {
        $cashier = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $cashier->id,
            'role' => BusinessUser::ROLE_BUTCHER_CASHIER,
        ]);

        $product = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'View Only',
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 2500,
        ]);

        $this->actingAs($cashier)
            ->get(route('butcher.catalog.index'))
            ->assertOk();

        $this->actingAs($cashier)
            ->get(route('butcher.catalog.products.show', $product))
            ->assertOk();

        $this->actingAs($cashier)
            ->get(route('butcher.catalog.products.create'))
            ->assertForbidden();

        $this->actingAs($cashier)
            ->post(route('butcher.catalog.products.store'), [
                'name' => 'Should Fail',
                'meat_type' => ButcherProduct::MEAT_BEEF,
                'unit' => ButcherProduct::UNIT_PER_KG,
                'default_price' => 1000,
            ])
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('butcher.catalog.products.edit', $product))
            ->assertForbidden();

        $this->actingAs($cashier)
            ->put(route('butcher.catalog.products.update', $product), [
                'name' => 'Hacked',
                'meat_type' => ButcherProduct::MEAT_BEEF,
                'unit' => ButcherProduct::UNIT_PER_KG,
                'default_price' => 1,
                'is_active' => '0',
            ])
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('butcher.catalog.price-rules.create', $product))
            ->assertForbidden();
    }

    public function test_manager_can_manage_catalog(): void
    {
        $manager = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $manager->id,
            'role' => BusinessUser::ROLE_BUTCHER_MANAGER,
        ]);

        $this->actingAs($manager)
            ->post(route('butcher.catalog.products.store'), [
                'name' => 'Manager Product',
                'cut_type_id' => $this->cutType->id,
                'meat_type' => ButcherProduct::MEAT_BEEF,
                'unit' => ButcherProduct::UNIT_PER_PIECE,
                'default_price' => 2000,
            ])
            ->assertRedirect();

        $product = ButcherProduct::query()->where('name', 'Manager Product')->firstOrFail();
        $this->assertSame(ButcherProduct::UNIT_PER_PIECE, $product->unit);

        $this->actingAs($manager)
            ->post(route('butcher.catalog.price-rules.store', $product), [
                'product_id' => $product->id,
                'customer_tier' => ButcherPriceRule::TIER_RETAIL,
                'price' => 2000,
                'valid_from' => now()->toDateString(),
                'is_active' => '1',
            ])
            ->assertRedirect();
    }

    public function test_catalog_index_filters_by_status_and_search(): void
    {
        $active = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Active Ribeye',
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 6000,
        ]);
        app(ButcherCatalogService::class)->setPriceRule($this->business, [
            'product_id' => $active->id,
            'customer_tier' => ButcherPriceRule::TIER_RETAIL,
            'price' => 6000,
            'valid_from' => now()->toDateString(),
        ]);
        app(ButcherCatalogService::class)->updateProduct($active->fresh(), [
            'name' => $active->name,
            'meat_type' => $active->meat_type,
            'unit' => $active->unit,
            'default_price' => 6000,
            'is_active' => true,
        ]);

        app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Inactive Brisket',
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 3500,
        ]);

        $this->actingAs($this->owner)
            ->get(route('butcher.catalog.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Active Ribeye')
            ->assertDontSee('Inactive Brisket');

        $this->actingAs($this->owner)
            ->get(route('butcher.catalog.index', ['q' => 'Brisket']))
            ->assertOk()
            ->assertSee('Inactive Brisket')
            ->assertDontSee('Active Ribeye');
    }

    private function seedCutStock(ButcherProduct $product, float $stockKg): void
    {
        $supplier = ButcherSupplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Catalog Supplier',
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
            'is_active' => true,
        ]);

        $delivery = app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $supplier->id,
            'outlet_id' => $this->outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 48.25,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $this->owner);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $cutting = app(ButcherCuttingService::class);

        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => $stockKg + 5,
        ]);

        $cutting->addCutOutput($session, [
            'cut_type_id' => $product->cut_type_id,
            'weight_kg' => $stockKg,
        ]);

        $cutting->closeSession($session->fresh());
    }
}
