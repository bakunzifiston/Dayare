<?php

namespace Tests\Feature\Butcher;

use App\Exceptions\Butcher\InsufficientButcherStockException;
use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherDisposalLog;
use App\Models\ButcherInventoryAdjustment;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use App\Models\ButcherOutlet;
use App\Models\ButcherStockCount;
use App\Models\ButcherStockTransfer;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherInventoryReconciliationService;
use App\Services\Butcher\ButcherProcurementService;
use App\Services\Butcher\ButcherStockTransferService;
use App\Services\Butcher\ButcherStorageService;
use App\Services\Butcher\InventoryConsumptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ButcherInventoryPhase3Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private ButcherOutlet $outletA;

    private ButcherOutlet $outletB;

    private ButcherSupplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-P3-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        $this->supplier = ButcherSupplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Supplier',
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
            'is_active' => true,
        ]);

        $this->outletA = ButcherOutlet::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Outlet A',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);

        $this->outletB = ButcherOutlet::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Outlet B',
            'district' => 'Kigali',
            'phone' => '+250788222222',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);
    }

    public function test_disposal_writes_ledger_row(): void
    {
        $batch = $this->createBatch($this->outletA, 48.25);

        app(ButcherStorageService::class)->logDisposal($batch, [
            'weight_disposed_kg' => 10,
            'reason' => ButcherDisposalLog::REASON_DAMAGED,
        ], $this->user);

        $this->assertDatabaseHas('butcher_inventory_movements', [
            'batch_id' => $batch->id,
            'type' => ButcherInventoryMovement::TYPE_DISPOSAL,
            'quantity_kg' => -10,
        ]);
        $this->assertEqualsWithDelta(38.25, (float) $batch->fresh()->remaining_weight_kg, 0.001);
    }

    public function test_adjustment_writes_ledger_row(): void
    {
        $batch = $this->createBatch($this->outletA, 48.25);

        app(ButcherStorageService::class)->logAdjustment($batch, [
            'weight_change_kg' => -3,
            'reason' => ButcherInventoryAdjustment::REASON_SHRINKAGE,
        ], $this->user);

        $this->assertDatabaseHas('butcher_inventory_movements', [
            'batch_id' => $batch->id,
            'type' => ButcherInventoryMovement::TYPE_ADJUSTMENT,
            'quantity_kg' => -3,
        ]);
    }

    public function test_stock_count_variance_writes_ledger_row(): void
    {
        $batch = $this->createBatch($this->outletA, 48.25);

        $this->actingAs($this->user)
            ->post(route('butcher.stock-counts.store'), [
                'outlet_id' => $this->outletA->id,
                'count_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $count = ButcherStockCount::query()->firstOrFail();
        $line = $count->lines()->firstOrFail();

        $this->actingAs($this->user)
            ->put(route('butcher.stock-counts.lines.update', $count), [
                'lines' => [['id' => $line->id, 'counted_weight_kg' => 40]],
            ]);

        $this->actingAs($this->user)
            ->post(route('butcher.stock-counts.complete', $count), ['apply_variances' => '1']);

        $this->assertDatabaseHas('butcher_inventory_movements', [
            'batch_id' => $batch->id,
            'type' => ButcherInventoryMovement::TYPE_STOCK_COUNT_VARIANCE,
            'quantity_kg' => -8.25,
        ]);
    }

    public function test_receipt_disposal_adjustment_reconcile_to_remaining(): void
    {
        $batch = $this->createBatch($this->outletA, 50);
        $storage = app(ButcherStorageService::class);

        $storage->logDisposal($batch, [
            'weight_disposed_kg' => 10,
            'reason' => ButcherDisposalLog::REASON_EXPIRED,
        ], $this->user);

        $storage->logAdjustment($batch->fresh(), [
            'weight_change_kg' => -2,
            'reason' => ButcherInventoryAdjustment::REASON_SHRINKAGE,
        ], $this->user);

        $batch->refresh();
        $ledgerSum = (float) ButcherInventoryMovement::query()->where('batch_id', $batch->id)->sum('quantity_kg');
        $this->assertEqualsWithDelta((float) $batch->remaining_weight_kg, $ledgerSum, 0.001);
        $this->assertEqualsWithDelta(38.0, $ledgerSum, 0.001);

        $mismatches = app(ButcherInventoryReconciliationService::class)->findMismatches($this->business);
        $this->assertTrue($mismatches->where('batch_id', $batch->id)->isEmpty());
    }

    public function test_disposal_rolls_back_when_ledger_write_fails(): void
    {
        $batch = $this->createBatch($this->outletA, 48.25);
        $before = (float) $batch->remaining_weight_kg;

        ButcherInventoryMovement::creating(function () {
            throw new \RuntimeException('Forced ledger failure');
        });

        try {
            app(ButcherStorageService::class)->logDisposal($batch, [
                'weight_disposed_kg' => 5,
                'reason' => ButcherDisposalLog::REASON_DAMAGED,
            ], $this->user);
            $this->assertTrue(false, 'Expected ledger failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Forced ledger failure', $e->getMessage());
        }

        $this->assertEqualsWithDelta($before, (float) $batch->fresh()->remaining_weight_kg, 0.001);
        $this->assertSame(0, DB::table('butcher_disposal_logs')->count());
        $this->assertSame(1, DB::table('butcher_inventory_movements')->where('type', ButcherInventoryMovement::TYPE_RECEIPT)->count());
    }

    public function test_consumption_service_single_and_multi_batch_fifo(): void
    {
        $older = $this->createBatch($this->outletA, 10, ButcherDelivery::MEAT_BEEF, now()->subDays(2));
        $newer = $this->createBatch($this->outletA, 20, ButcherDelivery::MEAT_BEEF, now()->subDay());

        $service = app(InventoryConsumptionService::class);
        $allocations = $service->consume(
            (int) $this->business->id,
            (int) $this->outletA->id,
            ButcherDelivery::MEAT_BEEF,
            15,
            $this->user,
        );

        $this->assertCount(2, $allocations);
        $this->assertSame($older->id, $allocations[0]['batch']->id);
        $this->assertEqualsWithDelta(10.0, $allocations[0]['quantity_kg'], 0.001);
        $this->assertSame($newer->id, $allocations[1]['batch']->id);
        $this->assertEqualsWithDelta(5.0, $allocations[1]['quantity_kg'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $older->fresh()->remaining_weight_kg, 0.001);
        $this->assertEqualsWithDelta(15.0, (float) $newer->fresh()->remaining_weight_kg, 0.001);
    }

    public function test_consumption_service_rejects_insufficient_stock(): void
    {
        $this->createBatch($this->outletA, 5);

        $this->expectException(InsufficientButcherStockException::class);

        app(InventoryConsumptionService::class)->consume(
            (int) $this->business->id,
            (int) $this->outletA->id,
            ButcherDelivery::MEAT_BEEF,
            10,
            $this->user,
        );
    }

    public function test_consumption_scopes_by_outlet_and_meat_type(): void
    {
        $this->createBatch($this->outletA, 10, ButcherDelivery::MEAT_BEEF);
        $this->createBatch($this->outletB, 10, ButcherDelivery::MEAT_BEEF);
        $this->createBatch($this->outletA, 10, ButcherDelivery::MEAT_PORK);

        $available = app(InventoryConsumptionService::class)->availableKg(
            (int) $this->business->id,
            (int) $this->outletA->id,
            ButcherDelivery::MEAT_BEEF,
        );

        $this->assertEqualsWithDelta(10.0, $available, 0.001);
    }

    public function test_transfer_happy_path_writes_paired_ledger_rows(): void
    {
        $batch = $this->createBatch($this->outletA, 40);

        $transfer = app(ButcherStockTransferService::class)->transfer($this->business, [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'batch_id' => $batch->id,
            'quantity_kg' => 12,
        ], $this->user);

        $this->assertInstanceOf(ButcherStockTransfer::class, $transfer);
        $this->assertEqualsWithDelta(28.0, (float) $batch->fresh()->remaining_weight_kg, 0.001);
        $this->assertEqualsWithDelta(12.0, (float) $transfer->destinationBatch->remaining_weight_kg, 0.001);
        $this->assertSame((int) $this->outletB->id, (int) $transfer->destinationBatch->outlet_id);

        $this->assertDatabaseHas('butcher_inventory_movements', [
            'batch_id' => $batch->id,
            'type' => ButcherInventoryMovement::TYPE_TRANSFER_OUT,
            'quantity_kg' => -12,
        ]);
        $this->assertDatabaseHas('butcher_inventory_movements', [
            'batch_id' => $transfer->destination_batch_id,
            'type' => ButcherInventoryMovement::TYPE_TRANSFER_IN,
            'quantity_kg' => 12,
        ]);
    }

    public function test_transfer_rejects_cross_business_outlet(): void
    {
        $other = Business::factory()->butcher()->create([
            'user_id' => User::factory()->create()->id,
            'registration_number' => 'RDB-P3-OTHER',
        ]);
        $foreignOutlet = ButcherOutlet::query()->create([
            'business_id' => $other->id,
            'name' => 'Foreign',
            'district' => 'Kigali',
            'phone' => '+250788999999',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);

        $batch = $this->createBatch($this->outletA, 20);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ButcherStockTransferService::class)->transfer($this->business, [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $foreignOutlet->id,
            'batch_id' => $batch->id,
            'quantity_kg' => 5,
        ], $this->user);
    }

    public function test_sequential_transfer_then_disposal_respects_updated_remaining(): void
    {
        $batch = $this->createBatch($this->outletA, 20);

        app(ButcherStockTransferService::class)->transfer($this->business, [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'batch_id' => $batch->id,
            'quantity_kg' => 15,
        ], $this->user);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ButcherStorageService::class)->logDisposal($batch->fresh(), [
            'weight_disposed_kg' => 10,
            'reason' => ButcherDisposalLog::REASON_DAMAGED,
        ], $this->user);
    }

    public function test_transfer_http_flow(): void
    {
        $batch = $this->createBatch($this->outletA, 25);

        $this->actingAs($this->user)
            ->post(route('butcher.transfers.store'), [
                'from_outlet_id' => $this->outletA->id,
                'to_outlet_id' => $this->outletB->id,
                'batch_id' => $batch->id,
                'quantity_kg' => 8,
            ])
            ->assertRedirect(route('butcher.transfers.index'));

        $this->assertEqualsWithDelta(17.0, (float) $batch->fresh()->remaining_weight_kg, 0.001);
    }

    public function test_batch_show_includes_movement_history_tab(): void
    {
        $batch = $this->createBatch($this->outletA, 10);

        $this->actingAs($this->user)
            ->get(route('butcher.inventory.batches.show', [$batch, 'tab' => 'movements']))
            ->assertOk()
            ->assertSee(__('Movement history'))
            ->assertSee('receipt');
    }

    private function createBatch(
        ButcherOutlet $outlet,
        float $weight,
        string $meatType = ButcherDelivery::MEAT_BEEF,
        ?\DateTimeInterface $receivedAt = null,
    ): ButcherInventoryBatch {
        $delivery = app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $outlet->id,
            'meat_type' => $meatType,
            'received_weight_kg' => $weight,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
            'received_at' => ($receivedAt ?? now())->format('Y-m-d H:i:s'),
        ], $this->user);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();

        if ($receivedAt !== null) {
            $batch->update(['received_at' => $receivedAt]);
        }

        return $batch->fresh();
    }
}
