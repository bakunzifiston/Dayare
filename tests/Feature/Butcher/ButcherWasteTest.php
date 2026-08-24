<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherDisposalLog;
use App\Models\ButcherInventoryAdjustment;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherWasteTest extends TestCase
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
            'registration_number' => 'RDB-WST-001',
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

    public function test_waste_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.waste.index'))
            ->assertOk()
            ->assertSee(__('Waste & adjustments'));
    }

    public function test_can_log_waste(): void
    {
        $batch = $this->createBatch();

        $this->actingAs($this->user)
            ->post(route('butcher.waste.store'), [
                'batch_id' => $batch->id,
                'weight_disposed_kg' => 5,
                'reason' => ButcherDisposalLog::REASON_DAMAGED,
            ])
            ->assertRedirect(route('butcher.waste.index'));

        $batch->refresh();
        $this->assertEqualsWithDelta(43.25, (float) $batch->remaining_weight_kg, 0.001);
        $this->assertDatabaseHas('butcher_disposal_logs', [
            'batch_id' => $batch->id,
            'weight_disposed_kg' => 5,
        ]);
    }

    public function test_can_log_positive_and_negative_adjustments(): void
    {
        $batch = $this->createBatch();

        $this->actingAs($this->user)
            ->post(route('butcher.waste.adjustments.store'), [
                'batch_id' => $batch->id,
                'weight_change_kg' => -3,
                'reason' => ButcherInventoryAdjustment::REASON_SHRINKAGE,
            ])
            ->assertRedirect(route('butcher.waste.index'));

        $batch->refresh();
        $this->assertEqualsWithDelta(45.25, (float) $batch->remaining_weight_kg, 0.001);

        $this->actingAs($this->user)
            ->post(route('butcher.waste.adjustments.store'), [
                'batch_id' => $batch->id,
                'weight_change_kg' => 1.5,
                'reason' => ButcherInventoryAdjustment::REASON_FOUND_STOCK,
            ])
            ->assertRedirect(route('butcher.waste.index'));

        $batch->refresh();
        $this->assertEqualsWithDelta(46.75, (float) $batch->remaining_weight_kg, 0.001);
    }

    private function createBatch(): ButcherInventoryBatch
    {
        $delivery = app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 48.25,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $this->user);

        return ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
    }
}
