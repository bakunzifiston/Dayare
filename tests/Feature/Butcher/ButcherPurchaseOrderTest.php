<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\ButcherDelivery;
use App\Models\ButcherDeliveryLine;
use App\Models\ButcherOutlet;
use App\Models\ButcherPurchaseOrder;
use App\Models\ButcherSupplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherPurchaseOrderTest extends TestCase
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
            'registration_number' => 'RDB-PO-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        BusinessUser::query()->updateOrCreate(
            ['business_id' => $this->business->id, 'user_id' => $this->user->id],
            ['role' => BusinessUser::ROLE_BUTCHER_OWNER]
        );

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

    public function test_po_create_send_confirm_and_receive_marks_delivered(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.purchase-orders.store'), [
                'supplier_id' => $this->supplier->id,
                'meat_type' => ButcherPurchaseOrder::MEAT_BEEF,
                'requested_weight_kg' => 50,
                'requested_date' => now()->toDateString(),
                'notes' => 'Weekly beef',
            ])
            ->assertRedirect();

        $order = ButcherPurchaseOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(ButcherPurchaseOrder::STATUS_DRAFT, $order->status);

        $this->actingAs($this->user)
            ->patch(route('butcher.purchase-orders.status', $order), [
                'status' => ButcherPurchaseOrder::STATUS_SENT,
            ])
            ->assertRedirect(route('butcher.purchase-orders.show', $order));

        $this->actingAs($this->user)
            ->patch(route('butcher.purchase-orders.status', $order), [
                'status' => ButcherPurchaseOrder::STATUS_CONFIRMED,
            ])
            ->assertRedirect();

        $this->assertSame(ButcherPurchaseOrder::STATUS_CONFIRMED, $order->fresh()->status);

        $this->actingAs($this->user)
            ->post(route('butcher.receiving.store'), [
                'purchase_order_id' => $order->id,
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'lines' => [
                    [
                        'meat_type' => ButcherDelivery::MEAT_BEEF,
                        'expected_weight_kg' => 50,
                        'received_weight_kg' => 48,
                        'unit_cost' => 3500,
                        'outcome' => ButcherDeliveryLine::OUTCOME_ACCEPTED,
                        'accepted_weight_kg' => 48,
                        'rejected_weight_kg' => 0,
                    ],
                ],
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(ButcherPurchaseOrder::STATUS_DELIVERED, $order->status);
        $this->assertStringContainsString('Under-delivery', (string) $order->notes);
        $this->assertSame(1, $order->deliveries()->count());
    }

    public function test_link_existing_delivery_marks_po_delivered(): void
    {
        $order = ButcherPurchaseOrder::query()->create([
            'business_id' => $this->business->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-'.$this->business->id.'-0001',
            'meat_type' => ButcherPurchaseOrder::MEAT_BEEF,
            'requested_weight_kg' => 40,
            'requested_date' => now()->toDateString(),
            'status' => ButcherPurchaseOrder::STATUS_CONFIRMED,
        ]);

        $this->actingAs($this->user)
            ->post(route('butcher.receiving.store'), [
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'lines' => [
                    [
                        'meat_type' => ButcherDelivery::MEAT_BEEF,
                        'received_weight_kg' => 42,
                        'unit_cost' => 3000,
                        'outcome' => ButcherDeliveryLine::OUTCOME_ACCEPTED,
                        'accepted_weight_kg' => 42,
                        'rejected_weight_kg' => 0,
                    ],
                ],
            ])
            ->assertRedirect();

        $delivery = ButcherDelivery::query()->first();
        $this->assertNotNull($delivery);
        $this->assertNull($delivery->purchase_order_id);

        $this->actingAs($this->user)
            ->post(route('butcher.purchase-orders.link-delivery', $order), [
                'delivery_id' => $delivery->id,
            ])
            ->assertRedirect(route('butcher.purchase-orders.show', $order));

        $this->assertSame($order->id, $delivery->fresh()->purchase_order_id);
        $this->assertSame(ButcherPurchaseOrder::STATUS_DELIVERED, $order->fresh()->status);
        $this->assertStringContainsString('Over-delivery', (string) $order->fresh()->notes);
    }

    public function test_cannot_view_other_business_purchase_order(): void
    {
        $otherOwner = User::factory()->create();
        $otherBusiness = Business::factory()->butcher()->create([
            'user_id' => $otherOwner->id,
            'registration_number' => 'RDB-PO-OTHER',
        ]);
        BusinessUser::query()->create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherOwner->id,
            'role' => BusinessUser::ROLE_BUTCHER_OWNER,
        ]);

        $otherSupplier = ButcherSupplier::query()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Abattoir',
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
            'is_active' => true,
        ]);

        $order = ButcherPurchaseOrder::query()->create([
            'business_id' => $otherBusiness->id,
            'supplier_id' => $otherSupplier->id,
            'po_number' => 'PO-OTHER-0001',
            'meat_type' => ButcherPurchaseOrder::MEAT_BEEF,
            'requested_weight_kg' => 10,
            'requested_date' => now()->toDateString(),
            'status' => ButcherPurchaseOrder::STATUS_DRAFT,
        ]);

        $this->actingAs($this->user)
            ->get(route('butcher.purchase-orders.show', $order))
            ->assertNotFound();
    }

    public function test_procurement_officer_can_create_po_cashier_cannot(): void
    {
        $officer = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $officer->id,
            'role' => BusinessUser::ROLE_BUTCHER_PROCUREMENT_OFFICER,
        ]);

        $this->actingAs($officer)
            ->post(route('butcher.purchase-orders.store'), [
                'supplier_id' => $this->supplier->id,
                'meat_type' => ButcherPurchaseOrder::MEAT_GOAT,
                'requested_weight_kg' => 25,
                'requested_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $cashier = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $cashier->id,
            'role' => BusinessUser::ROLE_BUTCHER_CASHIER,
        ]);

        $this->actingAs($cashier)
            ->get(route('butcher.purchase-orders.index'))
            ->assertForbidden();
    }
}
