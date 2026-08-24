<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherSupplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherReceivingTest extends TestCase
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
            'registration_number' => 'RDB-RCV-001',
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

    public function test_receiving_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.receiving.index'))
            ->assertOk()
            ->assertSee(__('Receiving'))
            ->assertSee(__('Receive delivery'));
    }

    public function test_good_delivery_creates_inventory_batch(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.receiving.store'), [
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'meat_type' => ButcherDelivery::MEAT_BEEF,
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
        $this->assertSame(ButcherInventoryBatch::STATUS_IN_STORAGE, $batch->status);
    }

    public function test_rejected_delivery_creates_rejection_log_not_inventory(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.receiving.store'), [
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'meat_type' => ButcherDelivery::MEAT_PORK,
                'received_weight_kg' => 20,
                'unit_cost_per_kg' => 3000,
                'condition' => ButcherDelivery::CONDITION_REJECTED,
            ])
            ->assertRedirect();

        $delivery = ButcherDelivery::query()->first();
        $this->assertNotNull($delivery);

        $this->assertDatabaseMissing('butcher_inventory_batches', ['delivery_id' => $delivery->id]);
        $this->assertDatabaseHas('butcher_delivery_rejections', ['delivery_id' => $delivery->id]);
    }

    public function test_cannot_view_other_business_delivery(): void
    {
        $otherBusiness = Business::factory()->butcher()->create([
            'user_id' => User::factory()->create()->id,
            'registration_number' => 'RDB-OTHER-003',
        ]);

        $delivery = ButcherDelivery::query()->create([
            'business_id' => $otherBusiness->id,
            'supplier_id' => ButcherSupplier::query()->create([
                'business_id' => $otherBusiness->id,
                'name' => 'Other Supplier',
                'supplier_type' => ButcherSupplier::TYPE_OTHER,
                'is_active' => true,
            ])->id,
            'outlet_id' => ButcherOutlet::query()->create([
                'business_id' => $otherBusiness->id,
                'name' => 'Other Outlet',
                'district' => 'Kigali',
                'phone' => '+250788999999',
                'status' => ButcherOutlet::STATUS_ACTIVE,
            ])->id,
            'delivery_number' => 'DEL-OTHER-001',
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 10,
            'unit_cost_per_kg' => 1000,
            'total_cost' => 10000,
            'condition' => ButcherDelivery::CONDITION_GOOD,
            'received_at' => now(),
            'received_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('butcher.receiving.show', $delivery))
            ->assertNotFound();
    }

    public function test_create_redirects_when_no_suppliers(): void
    {
        $this->supplier->delete();

        $this->actingAs($this->user)
            ->get(route('butcher.receiving.create'))
            ->assertRedirect(route('butcher.suppliers.index'));
    }
}
