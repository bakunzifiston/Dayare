<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherCutType;
use App\Models\ButcherDelivery;
use App\Models\ButcherDisposalLog;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use App\Models\ButcherOutlet;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherCuttingService;
use App\Services\Butcher\ButcherProcurementService;
use App\Services\Butcher\ButcherStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ButcherProcessingTest extends TestCase
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
            'registration_number' => 'RDB-PROC-001',
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

    public function test_processing_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.processing.index'))
            ->assertOk()
            ->assertSee(__('Processing'));
    }

    public function test_can_add_cut_type_via_http(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.processing.types.store'), [
                'name' => 'T-Bone',
                'meat_type' => ButcherCutType::MEAT_BEEF,
                'expected_yield_pct' => 85,
            ])
            ->assertRedirect(route('butcher.processing.types.index'));

        $this->assertDatabaseHas('butcher_cut_types', [
            'business_id' => $this->business->id,
            'name' => 'T-Bone',
        ]);
    }

    public function test_opening_session_does_not_change_batch_weight(): void
    {
        $batch = $this->createBatch(48.25);
        $cutting = app(ButcherCuttingService::class);

        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 20,
        ]);

        $batch->refresh();
        $this->assertEqualsWithDelta(48.25, (float) $batch->remaining_weight_kg, 0.001);
        $this->assertSame(ButcherInventoryBatch::STATUS_IN_STORAGE, $batch->status);
        $this->assertSame('open', $session->status);
        $this->assertStringStartsWith('CUT-', $session->session_number);
        $this->assertSame(1, $session->sources()->count());
    }

    public function test_close_session_calculates_wastage_via_http(): void
    {
        $cutType = $this->createCutType();
        $batch = $this->createBatch(48.25);
        $session = app(ButcherCuttingService::class)->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 20,
        ]);

        $this->actingAs($this->user)
            ->post(route('butcher.processing.sessions.outputs.store', $session), [
                'cut_type_id' => $cutType->id,
                'weight_kg' => 16.5,
            ])
            ->assertRedirect(route('butcher.processing.sessions.show', $session));

        $this->actingAs($this->user)
            ->post(route('butcher.processing.sessions.close', $session))
            ->assertRedirect(route('butcher.processing.sessions.show', $session));

        $session->refresh();
        $batch->refresh();
        $this->assertSame('closed', $session->status);
        $this->assertEqualsWithDelta(3.5, (float) $session->wastage_kg, 0.001);
        $this->assertEqualsWithDelta(28.25, (float) $batch->remaining_weight_kg, 0.001);
    }

    public function test_cannot_close_session_without_outputs(): void
    {
        $session = $this->openSession(15);

        $this->expectException(ValidationException::class);

        app(ButcherCuttingService::class)->closeSession($session, $this->user);
    }

    public function test_close_writes_consumption_and_output_ledger_rows(): void
    {
        $cutType = $this->createCutType();
        $batch = $this->createBatch(50);
        $cutting = app(ButcherCuttingService::class);

        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 20,
        ]);

        $output = $cutting->addCutOutput($session, [
            'cut_type_id' => $cutType->id,
            'weight_kg' => 18,
        ]);

        $cutting->closeSession($session, $this->user);

        $this->assertDatabaseHas('butcher_inventory_movements', [
            'batch_id' => $batch->id,
            'type' => ButcherInventoryMovement::TYPE_CUTTING_CONSUMPTION,
            'quantity_kg' => -20,
            'reference_id' => $session->id,
        ]);

        $this->assertDatabaseHas('butcher_inventory_movements', [
            'cut_output_id' => $output->id,
            'type' => ButcherInventoryMovement::TYPE_CUTTING_OUTPUT_IN,
            'quantity_kg' => 18,
        ]);

        $this->assertEqualsWithDelta(30.0, (float) $batch->fresh()->remaining_weight_kg, 0.001);
    }

    public function test_multi_source_session_reconciles_on_close(): void
    {
        $steak = $this->createCutType('Steak', 90);
        $batchA = $this->createBatch(40);
        $batchB = $this->createBatch(30);
        $cutting = app(ButcherCuttingService::class);

        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batchA->id,
            'source_weight_kg' => 25,
        ]);

        $cutting->addSource($session, [
            'batch_id' => $batchB->id,
            'source_weight_kg' => 15,
        ]);

        $this->assertEqualsWithDelta(40.0, (float) $batchA->fresh()->remaining_weight_kg, 0.001);
        $this->assertEqualsWithDelta(30.0, (float) $batchB->fresh()->remaining_weight_kg, 0.001);

        $cutting->addCutOutput($session, ['cut_type_id' => $steak->id, 'weight_kg' => 35]);
        $cutting->closeSession($session->fresh(), $this->user);

        $session->refresh();
        $this->assertEqualsWithDelta(40.0, (float) $session->source_weight_kg, 0.001);
        $this->assertEqualsWithDelta(35.0, (float) $session->total_cuts_weight_kg, 0.001);
        $this->assertEqualsWithDelta(5.0, (float) $session->wastage_kg, 0.001);

        $consumption = (float) ButcherInventoryMovement::query()
            ->where('reference_type', \App\Models\ButcherCuttingSession::class)
            ->where('reference_id', $session->id)
            ->where('type', ButcherInventoryMovement::TYPE_CUTTING_CONSUMPTION)
            ->sum('quantity_kg');
        $outputs = (float) ButcherInventoryMovement::query()
            ->where('reference_type', \App\Models\ButcherCuttingSession::class)
            ->where('reference_id', $session->id)
            ->where('type', ButcherInventoryMovement::TYPE_CUTTING_OUTPUT_IN)
            ->sum('quantity_kg');

        $this->assertEqualsWithDelta(-40.0, $consumption, 0.001);
        $this->assertEqualsWithDelta(35.0, $outputs, 0.001);
        $this->assertEqualsWithDelta(abs($consumption), $outputs + (float) $session->wastage_kg, 0.001);

        $this->assertEqualsWithDelta(15.0, (float) $batchA->fresh()->remaining_weight_kg, 0.001);
        $this->assertEqualsWithDelta(15.0, (float) $batchB->fresh()->remaining_weight_kg, 0.001);
    }

    public function test_abandoned_open_session_leaves_inventory_untouched(): void
    {
        $batch = $this->createBatch(48.25);
        app(ButcherCuttingService::class)->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 20,
        ]);

        $this->assertEqualsWithDelta(48.25, (float) $batch->fresh()->remaining_weight_kg, 0.001);
        $this->assertSame(
            0,
            ButcherInventoryMovement::query()
                ->where('type', ButcherInventoryMovement::TYPE_CUTTING_CONSUMPTION)
                ->count()
        );
    }

    public function test_close_fails_when_stock_dropped_below_declared_source(): void
    {
        $cutType = $this->createCutType();
        $batch = $this->createBatch(30);
        $cutting = app(ButcherCuttingService::class);

        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 25,
        ]);

        $cutting->addCutOutput($session, [
            'cut_type_id' => $cutType->id,
            'weight_kg' => 20,
        ]);

        // Concurrent disposal reduces available stock below declared source.
        app(ButcherStorageService::class)->logDisposal($batch, [
            'weight_disposed_kg' => 10,
            'reason' => ButcherDisposalLog::REASON_DAMAGED,
        ], $this->user);

        try {
            $cutting->closeSession($session->fresh(), $this->user);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sources', $e->errors());
        }

        $this->assertSame('open', $session->fresh()->status);
        $this->assertEqualsWithDelta(20.0, (float) $batch->fresh()->remaining_weight_kg, 0.001);
        $this->assertSame(
            0,
            ButcherInventoryMovement::query()
                ->where('type', ButcherInventoryMovement::TYPE_CUTTING_CONSUMPTION)
                ->count()
        );
    }

    public function test_worked_traceability_example_100kg_beef(): void
    {
        $batch = $this->createBatch(100);
        $steak = $this->createCutType('Steak', 90);
        $ribs = $this->createCutType('Ribs', 85);
        $trim = $this->createCutType('Trim', 80);
        $cutting = app(ButcherCuttingService::class);

        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 60,
        ]);

        $this->assertEqualsWithDelta(100.0, (float) $batch->fresh()->remaining_weight_kg, 0.001);

        $cutting->addCutOutput($session, ['cut_type_id' => $steak->id, 'weight_kg' => 40]);
        $cutting->addCutOutput($session, ['cut_type_id' => $ribs->id, 'weight_kg' => 10]);
        $cutting->addCutOutput($session, ['cut_type_id' => $trim->id, 'weight_kg' => 5]);

        $cutting->closeSession($session->fresh(), $this->user);

        $session->refresh();
        $batch->refresh();

        $this->assertEqualsWithDelta(5.0, (float) $session->wastage_kg, 0.001);
        $this->assertEqualsWithDelta(55.0, (float) $session->total_cuts_weight_kg, 0.001);
        $this->assertEqualsWithDelta(40.0, (float) $batch->remaining_weight_kg, 0.001);

        $consumption = abs((float) ButcherInventoryMovement::query()
            ->where('batch_id', $batch->id)
            ->where('type', ButcherInventoryMovement::TYPE_CUTTING_CONSUMPTION)
            ->sum('quantity_kg'));
        $outputIn = (float) ButcherInventoryMovement::query()
            ->where('reference_id', $session->id)
            ->where('type', ButcherInventoryMovement::TYPE_CUTTING_OUTPUT_IN)
            ->sum('quantity_kg');

        $this->assertEqualsWithDelta(60.0, $consumption, 0.001);
        $this->assertEqualsWithDelta(55.0, $outputIn, 0.001);
        $this->assertEqualsWithDelta($consumption, $outputIn + (float) $session->wastage_kg, 0.001);
        $this->assertSame(3, $session->cutOutputs()->count());
    }

    private function createBatch(float $weight = 48.25): ButcherInventoryBatch
    {
        $delivery = app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => $weight,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $this->user);

        return ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
    }

    private function createCutType(string $name = 'Sirloin', float $yield = 85): ButcherCutType
    {
        return $this->business->butcherCutTypes()->create([
            'name' => $name,
            'meat_type' => ButcherCutType::MEAT_BEEF,
            'expected_yield_pct' => $yield,
            'is_active' => true,
        ]);
    }

    private function openSession(float $sourceWeightKg): \App\Models\ButcherCuttingSession
    {
        return app(ButcherCuttingService::class)->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $this->createBatch()->id,
            'source_weight_kg' => $sourceWeightKg,
        ]);
    }
}
