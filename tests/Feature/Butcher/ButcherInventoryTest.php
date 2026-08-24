<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherDisposalLog;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherSupplier;
use App\Models\ButcherTemperatureLog;
use App\Models\User;
use App\Services\Butcher\ButcherProcurementService;
use App\Services\Butcher\ButcherStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherInventoryTest extends TestCase
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
            'registration_number' => 'RDB-INV-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
            'butcher_fresh_max_temp_c' => 4,
            'butcher_frozen_max_temp_c' => -18,
            'butcher_batch_shelf_life_days' => 3,
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

    public function test_inventory_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.inventory.index'))
            ->assertOk()
            ->assertSee(__('Inventory'));
    }

    public function test_delivery_creates_batch_with_best_before_and_in_storage_status(): void
    {
        $delivery = $this->receiveDelivery();

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->first();
        $this->assertNotNull($batch);
        $this->assertSame(ButcherInventoryBatch::STATUS_IN_STORAGE, $batch->status);
        $this->assertNotNull($batch->best_before_date);
        $this->assertEqualsWithDelta(48.25, (float) $batch->initial_weight_kg, 0.001);
    }

    public function test_temperature_breach_is_detected_via_http(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.inventory.temperatures.store'), [
                'outlet_id' => $this->outlet->id,
                'storage_location' => 'Fridge A',
                'storage_type' => ButcherTemperatureLog::TYPE_FRESH,
                'temperature_celsius' => 8,
            ])
            ->assertRedirect(route('butcher.inventory.temperatures.index'));

        $log = ButcherTemperatureLog::query()->first();
        $this->assertNotNull($log);
        $this->assertTrue($log->is_breach);
    }

    public function test_expired_batches_are_marked_by_check(): void
    {
        $batch = $this->createBatch();
        $batch->update(['best_before_date' => now()->subDay()->toDateString()]);

        $expired = app(ButcherStorageService::class)->checkExpiringBatches($this->business);

        $this->assertCount(1, $expired);
        $this->assertSame(ButcherInventoryBatch::STATUS_EXPIRED, $batch->fresh()->status);
    }

    public function test_disposal_reduces_remaining_weight_via_http(): void
    {
        $batch = $this->createBatch();

        $this->actingAs($this->user)
            ->post(route('butcher.inventory.disposals.store'), [
                'batch_id' => $batch->id,
                'weight_disposed_kg' => 10,
                'reason' => ButcherDisposalLog::REASON_DAMAGED,
            ])
            ->assertRedirect(route('butcher.inventory.disposals.index'));

        $batch->refresh();
        $this->assertEqualsWithDelta(38.25, (float) $batch->remaining_weight_kg, 0.001);
        $this->assertSame(ButcherInventoryBatch::STATUS_PARTIALLY_USED, $batch->status);
    }

    public function test_cannot_view_other_business_batch(): void
    {
        $otherUser = User::factory()->create();
        $otherBusiness = Business::factory()->butcher()->create([
            'user_id' => $otherUser->id,
            'registration_number' => 'RDB-OTHER-004',
        ]);

        $otherSupplier = ButcherSupplier::query()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Supplier',
            'supplier_type' => ButcherSupplier::TYPE_OTHER,
            'is_active' => true,
        ]);

        $otherOutlet = ButcherOutlet::query()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other',
            'district' => 'Kigali',
            'phone' => '+250788999999',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);

        $delivery = app(ButcherProcurementService::class)->receiveDelivery($otherBusiness, [
            'supplier_id' => $otherSupplier->id,
            'outlet_id' => $otherOutlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 10,
            'unit_cost_per_kg' => 1000,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $otherUser);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('butcher.inventory.batches.show', $batch))
            ->assertNotFound();
    }

    private function receiveDelivery(): ButcherDelivery
    {
        return app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 48.25,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $this->user);
    }

    private function createBatch(): ButcherInventoryBatch
    {
        return ButcherInventoryBatch::query()
            ->where('delivery_id', $this->receiveDelivery()->id)
            ->firstOrFail();
    }
}
