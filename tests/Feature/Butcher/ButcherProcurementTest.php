<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherPurchaseOrder;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherProcurementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private ButcherSupplier $supplier;

    private ButcherOutlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-PROC-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        $this->supplier = ButcherSupplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Nyagatare Abattoir',
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
            'is_active' => true,
        ]);

        $this->outlet = ButcherOutlet::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Main Shop',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'is_primary' => true,
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);
    }

    public function test_procurement_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.procurement.index'))
            ->assertOk()
            ->assertSee(__('Stock procurement'));
    }

    public function test_can_create_purchase_order(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.procurement.orders.store'), [
                'supplier_id' => $this->supplier->id,
                'meat_type' => ButcherPurchaseOrder::MEAT_BEEF,
                'requested_weight_kg' => 120.5,
                'requested_date' => now()->addDay()->toDateString(),
            ])
            ->assertRedirect();

        $order = ButcherPurchaseOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(ButcherPurchaseOrder::STATUS_DRAFT, $order->status);
        $this->assertStringStartsWith('PO-', $order->po_number);
    }

    public function test_good_delivery_creates_inventory_batch_and_marks_po_delivered(): void
    {
        $order = app(ButcherProcurementService::class)->createPurchaseOrder($this->business, [
            'supplier_id' => $this->supplier->id,
            'meat_type' => ButcherPurchaseOrder::MEAT_GOAT,
            'requested_weight_kg' => 50,
            'requested_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->post(route('butcher.procurement.deliveries.store'), [
                'purchase_order_id' => $order->id,
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'meat_type' => ButcherPurchaseOrder::MEAT_GOAT,
                'received_weight_kg' => 48.25,
                'unit_cost_per_kg' => 3500,
                'condition' => ButcherDelivery::CONDITION_GOOD,
                'certificate_ref' => 'CERT-EXT-001',
                'certificate_issuer' => 'RFA',
            ])
            ->assertRedirect();

        $delivery = ButcherDelivery::query()->first();
        $this->assertNotNull($delivery);
        $this->assertSame(168875.0, (float) $delivery->total_cost);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->first();
        $this->assertNotNull($batch);
        $this->assertSame('CERT-EXT-001', $delivery->certificate_ref);
        $this->assertSame(ButcherInventoryBatch::STATUS_IN_STORAGE, $batch->status);

        $order->refresh();
        $this->assertSame(ButcherPurchaseOrder::STATUS_DELIVERED, $order->status);
    }

    public function test_rejected_delivery_creates_rejection_log_not_inventory(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.procurement.deliveries.store'), [
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'meat_type' => ButcherPurchaseOrder::MEAT_BEEF,
                'received_weight_kg' => 20,
                'unit_cost_per_kg' => 4000,
                'condition' => ButcherDelivery::CONDITION_REJECTED,
                'notes' => 'Off smell',
            ])
            ->assertRedirect();

        $delivery = ButcherDelivery::query()->first();
        $this->assertNotNull($delivery);
        $this->assertDatabaseMissing('butcher_inventory_batches', ['delivery_id' => $delivery->id]);
        $this->assertDatabaseHas('butcher_delivery_rejections', ['delivery_id' => $delivery->id]);
    }

    public function test_inactive_supplier_cannot_be_used_on_delivery(): void
    {
        $inactive = ButcherSupplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Inactive Supplier',
            'supplier_type' => ButcherSupplier::TYPE_FARM,
            'is_active' => false,
        ]);

        $this->actingAs($this->user)
            ->post(route('butcher.procurement.deliveries.store'), [
                'supplier_id' => $inactive->id,
                'outlet_id' => $this->outlet->id,
                'meat_type' => ButcherPurchaseOrder::MEAT_BEEF,
                'received_weight_kg' => 10,
                'unit_cost_per_kg' => 1000,
                'condition' => ButcherDelivery::CONDITION_GOOD,
            ])
            ->assertSessionHasErrors(['supplier_id']);
    }

    public function test_can_update_purchase_order_status(): void
    {
        $order = app(ButcherProcurementService::class)->createPurchaseOrder($this->business, [
            'supplier_id' => $this->supplier->id,
            'meat_type' => ButcherPurchaseOrder::MEAT_POULTRY,
            'requested_weight_kg' => 30,
            'requested_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->patch(route('butcher.procurement.orders.status', $order), [
                'status' => ButcherPurchaseOrder::STATUS_SENT,
            ])
            ->assertRedirect(route('butcher.procurement.orders.show', $order));

        $this->assertSame(ButcherPurchaseOrder::STATUS_SENT, $order->fresh()->status);
    }
}
