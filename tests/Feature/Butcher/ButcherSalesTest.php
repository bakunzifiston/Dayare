<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherCustomer;
use App\Models\ButcherCutType;
use App\Models\ButcherDelivery;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherProduct;
use App\Models\ButcherSale;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherCatalogService;
use App\Services\Butcher\ButcherCuttingService;
use App\Services\Butcher\ButcherProcurementService;
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

        $customer = ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Hotel',
            'phone' => '+250788444444',
            'tier' => ButcherCustomer::TIER_WHOLESALE,
        ]);

        $order = app(ButcherSalesService::class)->createOrder($this->business, [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity_kg' => 10],
            ],
        ]);

        $this->assertGreaterThan(0, (float) $order->total_amount);
        $this->assertCount(1, $order->items);

        app(ButcherSalesService::class)->updateOrderStatus($order, \App\Models\ButcherOrder::STATUS_CONFIRMED);
        $this->assertSame(\App\Models\ButcherOrder::STATUS_CONFIRMED, $order->fresh()->status);
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
