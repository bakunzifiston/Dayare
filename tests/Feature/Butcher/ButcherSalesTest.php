<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherCustomer;
use App\Models\ButcherCutType;
use App\Models\ButcherDelivery;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use App\Models\ButcherOrder;
use App\Models\ButcherOutlet;
use App\Models\ButcherPriceRule;
use App\Models\ButcherProduct;
use App\Models\ButcherReturn;
use App\Models\ButcherSale;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherCatalogService;
use App\Services\Butcher\ButcherCuttingService;
use App\Services\Butcher\ButcherOrderFulfillmentService;
use App\Services\Butcher\ButcherProcurementService;
use App\Services\Butcher\ButcherReturnService;
use App\Services\Butcher\ButcherSalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ButcherSalesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private ButcherOutlet $outlet;

    private ButcherSupplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-SAL-001',
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

    public function test_sales_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.sales.index'))
            ->assertOk()
            ->assertSee(__('Sales'));
    }

    public function test_pos_sale_deducts_cut_stock_and_generates_receipt(): void
    {
        [$product, $output] = $this->seedProductWithStock(20);

        $sale = app(ButcherSalesService::class)->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 25000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_kg' => 5,
                ],
            ],
        ], $this->user);

        $this->assertSame(ButcherSale::STATUS_COMPLETED, $sale->status);
        $this->assertNotNull($sale->receipt_path);
        Storage::disk('public')->assertExists($sale->receipt_path);

        $output->refresh();
        $this->assertEqualsWithDelta(15, (float) $output->remaining_weight_kg, 0.001);

        $item = $sale->items->first();
        $this->assertNotNull($item?->cut_output_id);
        $this->assertSame($output->id, (int) $item->cut_output_id);

        $this->assertTrue(
            ButcherInventoryMovement::query()
                ->where('cut_output_id', $output->id)
                ->where('type', ButcherInventoryMovement::TYPE_SALE)
                ->where('reference_id', $sale->id)
                ->exists()
        );
    }

    public function test_credit_sale_updates_customer_balance(): void
    {
        [$product] = $this->seedProductWithStock(10);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Wholesale Co',
            'phone' => '+250788222222',
            'tier' => ButcherCustomer::TIER_WHOLESALE,
            'credit_limit' => 100000,
        ]);

        app(ButcherSalesService::class)->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'customer_id' => $customer->id,
            'payment_method' => ButcherSale::PAYMENT_CREDIT,
            'amount_paid' => 0,
            'items' => [
                ['product_id' => $product->id, 'quantity_kg' => 2],
            ],
        ], $this->user);

        $customer->refresh();
        $this->assertGreaterThan(0, (float) $customer->outstanding_balance);
    }

    public function test_cancel_sale_restores_stock_and_reverses_credit(): void
    {
        [$product, $output] = $this->seedProductWithStock(10);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Credit Customer',
            'phone' => '+250788333333',
            'tier' => ButcherCustomer::TIER_RETAIL,
            'credit_limit' => 50000,
        ]);

        $sales = app(ButcherSalesService::class);
        $sale = $sales->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'customer_id' => $customer->id,
            'payment_method' => ButcherSale::PAYMENT_CREDIT,
            'amount_paid' => 0,
            'items' => [
                ['product_id' => $product->id, 'quantity_kg' => 3],
            ],
        ], $this->user);

        $customer->refresh();
        $balanceBefore = (float) $customer->outstanding_balance;

        $sales->cancelSale($sale);

        $output->refresh();
        $this->assertEqualsWithDelta(10, (float) $output->remaining_weight_kg, 0.001);
        $this->assertEqualsWithDelta(0, (float) $customer->fresh()->outstanding_balance, 0.01);
        $this->assertSame(ButcherSale::STATUS_CANCELLED, $sale->fresh()->status);
        $this->assertGreaterThan(0, $balanceBefore);
    }

    public function test_insufficient_stock_throws_validation_error(): void
    {
        [$product] = $this->seedProductWithStock(2);

        $this->expectException(ValidationException::class);

        app(ButcherSalesService::class)->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 50000,
            'items' => [
                ['product_id' => $product->id, 'quantity_kg' => 5],
            ],
        ], $this->user);
    }

    public function test_create_order_and_update_status(): void
    {
        $product = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Ribeye',
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 8000,
        ]);
        app(ButcherCatalogService::class)->setPriceRule($this->business, [
            'product_id' => $product->id,
            'customer_tier' => \App\Models\ButcherPriceRule::TIER_RETAIL,
            'price' => 8000,
            'valid_from' => now()->toDateString(),
        ]);
        app(ButcherCatalogService::class)->updateProduct($product->fresh(), [
            'name' => $product->name,
            'meat_type' => $product->meat_type,
            'unit' => $product->unit,
            'default_price' => 8000,
            'is_active' => true,
        ]);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Hotel',
            'phone' => '+250788444444',
            'tier' => ButcherCustomer::TIER_WHOLESALE,
            'credit_limit' => 500000,
        ]);

        $order = app(ButcherSalesService::class)->createOrder($this->business, [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity_kg' => 10],
            ],
        ]);

        $this->assertGreaterThan(0, (float) $order->total_amount);
        $this->assertCount(1, $order->items);

        app(ButcherSalesService::class)->updateOrderStatus($order, ButcherOrder::STATUS_CONFIRMED);
        $this->assertSame(ButcherOrder::STATUS_CONFIRMED, $order->fresh()->status);
    }

    public function test_order_confirmation_enforces_credit_limit(): void
    {
        [$product] = $this->seedProductWithStock(20);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Low Credit',
            'phone' => '+250788555555',
            'tier' => ButcherCustomer::TIER_WHOLESALE,
            'credit_limit' => 1000,
            'outstanding_balance' => 0,
        ]);

        $order = app(ButcherSalesService::class)->createOrder($this->business, [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity_kg' => 5],
            ],
        ]);

        $this->expectException(ValidationException::class);
        app(ButcherSalesService::class)->updateOrderStatus($order, ButcherOrder::STATUS_CONFIRMED);
    }

    public function test_fulfilling_ready_order_creates_sale_and_deducts_stock(): void
    {
        [$product, $output] = $this->seedProductWithStock(20);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Wholesale Hotel',
            'phone' => '+250788666666',
            'tier' => ButcherCustomer::TIER_WHOLESALE,
            'credit_limit' => 500000,
        ]);

        $sales = app(ButcherSalesService::class);
        $order = $sales->createOrder($this->business, [
            'customer_id' => $customer->id,
            'outlet_id' => $this->outlet->id,
            'deposit_paid' => 0,
            'items' => [
                ['product_id' => $product->id, 'quantity_kg' => 8],
            ],
        ]);

        $sales->updateOrderStatus($order, ButcherOrder::STATUS_CONFIRMED);
        $sales->updateOrderStatus($order->fresh(), ButcherOrder::STATUS_READY);

        $sale = app(ButcherOrderFulfillmentService::class)->fulfill($order->fresh(), [
            'outlet_id' => $this->outlet->id,
            'payment_method' => ButcherSale::PAYMENT_CREDIT,
            'amount_paid' => 0,
        ], $this->user);

        $order->refresh();
        $output->refresh();

        $this->assertSame(ButcherOrder::STATUS_FULFILLED, $order->status);
        $this->assertSame($sale->id, (int) $order->sale_id);
        $this->assertSame(ButcherSale::STATUS_COMPLETED, $sale->status);
        $this->assertEqualsWithDelta(12, (float) $output->remaining_weight_kg, 0.001);
        $this->assertSame($output->id, (int) $sale->items->first()->cut_output_id);
        $this->assertGreaterThan(0, (float) $customer->fresh()->outstanding_balance);

        $this->assertTrue(
            ButcherInventoryMovement::query()
                ->where('type', ButcherInventoryMovement::TYPE_SALE)
                ->where('cut_output_id', $output->id)
                ->exists()
        );
    }

    public function test_double_fulfillment_is_rejected(): void
    {
        [$product] = $this->seedProductWithStock(20);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Once Only',
            'phone' => '+250788777777',
            'tier' => ButcherCustomer::TIER_WHOLESALE,
            'credit_limit' => 500000,
        ]);

        $sales = app(ButcherSalesService::class);
        $order = $sales->createOrder($this->business, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity_kg' => 4]],
        ]);
        $sales->updateOrderStatus($order, ButcherOrder::STATUS_CONFIRMED);
        $sales->updateOrderStatus($order->fresh(), ButcherOrder::STATUS_READY);

        $fulfillment = app(ButcherOrderFulfillmentService::class);
        $fulfillment->fulfill($order->fresh(), [
            'outlet_id' => $this->outlet->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => (float) $order->fresh()->total_amount,
        ], $this->user);

        $this->expectException(ValidationException::class);
        $fulfillment->fulfill($order->fresh(), [
            'outlet_id' => $this->outlet->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => (float) $order->fresh()->total_amount,
        ], $this->user);
    }

    public function test_return_restores_stock_and_cannot_exceed_sold_qty(): void
    {
        [$product, $output] = $this->seedProductWithStock(15);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Return Customer',
            'phone' => '+250788888888',
            'tier' => ButcherCustomer::TIER_RETAIL,
            'credit_limit' => 100000,
        ]);

        $sale = app(ButcherSalesService::class)->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'customer_id' => $customer->id,
            'payment_method' => ButcherSale::PAYMENT_CREDIT,
            'amount_paid' => 0,
            'items' => [['product_id' => $product->id, 'quantity_kg' => 5]],
        ], $this->user);

        $item = $sale->items->first();
        $balanceAfterSale = (float) $customer->fresh()->outstanding_balance;

        $return = app(ButcherReturnService::class)->processReturn($sale, [
            'sale_item_id' => $item->id,
            'quantity_kg' => 2,
            'reason' => 'Customer complaint',
        ], $this->user);

        $output->refresh();
        $this->assertEqualsWithDelta(12, (float) $output->remaining_weight_kg, 0.001);
        $this->assertInstanceOf(ButcherReturn::class, $return);
        $this->assertGreaterThan(0, (float) $return->credit_reversed);
        $this->assertLessThan($balanceAfterSale, (float) $customer->fresh()->outstanding_balance);

        $this->assertTrue(
            ButcherInventoryMovement::query()
                ->where('type', ButcherInventoryMovement::TYPE_RETURN_IN)
                ->where('cut_output_id', $output->id)
                ->where('reference_type', ButcherReturn::class)
                ->exists()
        );

        $this->expectException(ValidationException::class);
        app(ButcherReturnService::class)->processReturn($sale->fresh(), [
            'sale_item_id' => $item->id,
            'quantity_kg' => 4,
        ], $this->user);
    }

    public function test_receiving_cutting_sale_return_traceability_chain(): void
    {
        $delivery = app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 100,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $this->user);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $steak = $this->business->butcherCutTypes()->create([
            'name' => 'Steak',
            'meat_type' => ButcherCutType::MEAT_BEEF,
            'expected_yield_pct' => 90,
            'is_active' => true,
        ]);

        $product = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Fresh Steak',
            'cut_type_id' => $steak->id,
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => 6000,
        ]);
        app(ButcherCatalogService::class)->setPriceRule($this->business, [
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

        $cutting = app(ButcherCuttingService::class);
        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 60,
        ]);
        $output = $cutting->addCutOutput($session, ['cut_type_id' => $steak->id, 'weight_kg' => 40]);
        $cutting->closeSession($session->fresh(), $this->user);

        $this->assertEqualsWithDelta(40, (float) $batch->fresh()->remaining_weight_kg, 0.001);

        $sale = app(ButcherSalesService::class)->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 60000,
            'items' => [['product_id' => $product->id, 'quantity_kg' => 10]],
        ], $this->user);

        $this->assertSame($output->id, (int) $sale->items->first()->cut_output_id);
        $this->assertEqualsWithDelta(30, (float) $output->fresh()->remaining_weight_kg, 0.001);

        app(ButcherReturnService::class)->processReturn($sale, [
            'sale_item_id' => $sale->items->first()->id,
            'quantity_kg' => 2,
            'reason' => 'Trim quality',
        ], $this->user);

        $this->assertEqualsWithDelta(32, (float) $output->fresh()->remaining_weight_kg, 0.001);
        $this->assertEqualsWithDelta(10, abs((float) ButcherInventoryMovement::query()
            ->where('cut_output_id', $output->id)
            ->where('type', ButcherInventoryMovement::TYPE_SALE)
            ->sum('quantity_kg')), 0.001);
        $this->assertEqualsWithDelta(2, (float) ButcherInventoryMovement::query()
            ->where('cut_output_id', $output->id)
            ->where('type', ButcherInventoryMovement::TYPE_RETURN_IN)
            ->sum('quantity_kg'), 0.001);
    }

    public function test_fulfill_and_return_via_http(): void
    {
        [$product, $output] = $this->seedProductWithStock(20);

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'HTTP Hotel',
            'phone' => '+250788000111',
            'tier' => ButcherCustomer::TIER_WHOLESALE,
            'credit_limit' => 500000,
        ]);

        $this->actingAs($this->user)
            ->post(route('butcher.sales.orders.store'), [
                'customer_id' => $customer->id,
                'outlet_id' => $this->outlet->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity_kg' => 6],
                ],
            ])
            ->assertRedirect();

        $order = ButcherOrder::query()->firstOrFail();

        $this->actingAs($this->user)
            ->patch(route('butcher.sales.orders.status', $order), ['status' => ButcherOrder::STATUS_CONFIRMED])
            ->assertRedirect();
        $this->actingAs($this->user)
            ->patch(route('butcher.sales.orders.status', $order), ['status' => ButcherOrder::STATUS_READY])
            ->assertRedirect();

        $this->actingAs($this->user)
            ->post(route('butcher.sales.orders.fulfill', $order), [
                'outlet_id' => $this->outlet->id,
                'payment_method' => ButcherSale::PAYMENT_CASH,
                'amount_paid' => (float) $order->fresh()->total_amount,
            ])
            ->assertRedirect();

        $sale = ButcherSale::query()->latest('id')->firstOrFail();
        $this->assertSame(ButcherOrder::STATUS_FULFILLED, $order->fresh()->status);
        $this->assertEqualsWithDelta(14, (float) $output->fresh()->remaining_weight_kg, 0.001);

        $this->actingAs($this->user)
            ->get(route('butcher.sales.show', $sale))
            ->assertOk()
            ->assertSee((string) $output->id);

        $this->actingAs($this->user)
            ->post(route('butcher.sales.returns.store', $sale), [
                'sale_item_id' => $sale->items()->first()->id,
                'quantity_kg' => 1,
                'reason' => 'HTTP return',
            ])
            ->assertRedirect(route('butcher.sales.show', $sale));

        $this->assertEqualsWithDelta(15, (float) $output->fresh()->remaining_weight_kg, 0.001);
    }

    /**
     * @return array{0: ButcherProduct, 1: \App\Models\ButcherCutOutput}
     */
    private function seedProductWithStock(float $stockKg): array
    {
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
            'is_active' => true,
        ]);

        app(ButcherCatalogService::class)->updateProduct($product->fresh(), [
            'name' => $product->name,
            'cut_type_id' => $product->cut_type_id,
            'meat_type' => $product->meat_type,
            'unit' => $product->unit,
            'default_price' => $product->default_price,
            'is_active' => true,
        ]);

        $delivery = app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 48.25,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $this->user);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $cutting = app(ButcherCuttingService::class);

        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => $stockKg + 5,
        ]);

        $output = $cutting->addCutOutput($session, [
            'cut_type_id' => $cutType->id,
            'weight_kg' => $stockKg,
        ]);

        $cutting->closeSession($session->fresh());

        return [$product->fresh(), $output->fresh()];
    }
}
