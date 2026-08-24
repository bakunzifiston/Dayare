<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherCutType;
use App\Models\ButcherDelivery;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherCuttingService;
use App\Services\Butcher\ButcherProcurementService;
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

    public function test_opening_session_deducts_batch_weight(): void
    {
        $batch = $this->createBatch();
        $cutting = app(ButcherCuttingService::class);

        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 20,
        ]);

        $batch->refresh();
        $this->assertEqualsWithDelta(28.25, (float) $batch->remaining_weight_kg, 0.001);
        $this->assertSame(ButcherInventoryBatch::STATUS_PARTIALLY_USED, $batch->status);
        $this->assertSame('open', $session->status);
        $this->assertStringStartsWith('CUT-', $session->session_number);
    }

    public function test_close_session_calculates_wastage_via_http(): void
    {
        $cutType = $this->createCutType();
        $session = $this->openSession(20);

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
        $this->assertSame('closed', $session->status);
        $this->assertEqualsWithDelta(3.5, (float) $session->wastage_kg, 0.001);
    }

    public function test_cannot_close_session_without_outputs(): void
    {
        $session = $this->openSession(15);

        $this->expectException(ValidationException::class);

        app(ButcherCuttingService::class)->closeSession($session);
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

    private function createCutType(): ButcherCutType
    {
        return $this->business->butcherCutTypes()->create([
            'name' => 'Sirloin',
            'meat_type' => ButcherCutType::MEAT_BEEF,
            'expected_yield_pct' => 85,
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
