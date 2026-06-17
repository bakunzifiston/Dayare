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

class ButcherCuttingTest extends TestCase
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
            'registration_number' => 'RDB-CUT-001',
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

    public function test_cutting_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.cutting.index'))
            ->assertOk()
            ->assertSee(__('Cutting & processing'));
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

    public function test_cannot_use_expired_batch(): void
    {
        $batch = $this->createBatch();
        $batch->update([
            'best_before_date' => now()->subDay()->toDateString(),
            'status' => ButcherInventoryBatch::STATUS_EXPIRED,
        ]);

        $this->expectException(ValidationException::class);

        app(ButcherCuttingService::class)->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 10,
        ]);
    }

    public function test_cannot_close_session_without_outputs(): void
    {
        $session = $this->openSession(15);

        $this->expectException(ValidationException::class);

        app(ButcherCuttingService::class)->closeSession($session);
    }

    public function test_close_session_calculates_wastage(): void
    {
        $cutType = $this->createCutType();
        $session = $this->openSession(20);
        $cutting = app(ButcherCuttingService::class);

        $cutting->addCutOutput($session, [
            'cut_type_id' => $cutType->id,
            'weight_kg' => 16.5,
        ]);

        $cutting->closeSession($session->fresh());

        $session->refresh();
        $this->assertSame('closed', $session->status);
        $this->assertNotNull($session->closed_at);
        $this->assertEqualsWithDelta(3.5, (float) $session->wastage_kg, 0.001);
        $this->assertEqualsWithDelta(17.5, (float) $session->wastage_pct, 0.1);
    }

    public function test_label_generation_marks_output_printed(): void
    {
        $cutType = $this->createCutType();
        $session = $this->openSession(10);
        $cutting = app(ButcherCuttingService::class);

        $output = $cutting->addCutOutput($session, [
            'cut_type_id' => $cutType->id,
            'weight_kg' => 8,
        ]);

        $path = $cutting->generateLabel($output->fresh());

        $output->refresh();
        $this->assertTrue($output->label_printed);
        $this->assertSame($path, $output->label_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_full_session_flow_via_http(): void
    {
        $batch = $this->createBatch();
        $cutType = $this->createCutType();

        $this->actingAs($this->user)
            ->post(route('butcher.cutting.sessions.store'), [
                'outlet_id' => $this->outlet->id,
                'batch_id' => $batch->id,
                'source_weight_kg' => 12,
            ])
            ->assertRedirect();

        $session = $this->business->butcherCuttingSessions()->first();
        $this->assertNotNull($session);

        $this->actingAs($this->user)
            ->post(route('butcher.cutting.sessions.outputs.store', $session), [
                'cut_type_id' => $cutType->id,
                'weight_kg' => 10,
            ])
            ->assertRedirect(route('butcher.cutting.sessions.show', $session));

        $this->actingAs($this->user)
            ->post(route('butcher.cutting.sessions.close', $session))
            ->assertRedirect(route('butcher.cutting.sessions.show', $session));

        $session->refresh();
        $this->assertSame('closed', $session->status);
        $this->assertEqualsWithDelta(2, (float) $session->wastage_kg, 0.001);
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
            'name' => 'T-Bone',
            'meat_type' => ButcherCutType::MEAT_BEEF,
            'expected_yield_pct' => 85,
            'is_active' => true,
        ]);
    }

    private function openSession(float $sourceWeight): \App\Models\ButcherCuttingSession
    {
        $batch = $this->createBatch();

        return app(ButcherCuttingService::class)->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => $sourceWeight,
        ]);
    }
}
