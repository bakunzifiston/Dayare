<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherStockCount;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherStockCountTest extends TestCase
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
            'registration_number' => 'RDB-CNT-001',
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

    public function test_stock_counts_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.stock-counts.index'))
            ->assertOk()
            ->assertSee(__('Stock counts'));
    }

    public function test_can_start_count_save_lines_and_complete_with_adjustments(): void
    {
        $batch = $this->createBatch();

        $this->actingAs($this->user)
            ->post(route('butcher.stock-counts.store'), [
                'outlet_id' => $this->outlet->id,
                'count_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $count = ButcherStockCount::query()->first();
        $this->assertNotNull($count);
        $this->assertSame(ButcherStockCount::STATUS_DRAFT, $count->status);
        $this->assertCount(1, $count->lines);

        $line = $count->lines()->first();

        $this->actingAs($this->user)
            ->put(route('butcher.stock-counts.lines.update', $count), [
                'lines' => [
                    [
                        'id' => $line->id,
                        'counted_weight_kg' => 40,
                        'notes' => 'Scale reading',
                    ],
                ],
            ])
            ->assertRedirect(route('butcher.stock-counts.show', $count));

        $line->refresh();
        $this->assertEqualsWithDelta(40.0, (float) $line->counted_weight_kg, 0.001);
        $this->assertEqualsWithDelta(-8.25, (float) $line->variance_kg, 0.001);

        $this->actingAs($this->user)
            ->post(route('butcher.stock-counts.complete', $count), [
                'apply_variances' => '1',
            ])
            ->assertRedirect(route('butcher.stock-counts.show', $count));

        $count->refresh();
        $batch->refresh();
        $this->assertSame(ButcherStockCount::STATUS_COMPLETED, $count->status);
        $this->assertEqualsWithDelta(40.0, (float) $batch->remaining_weight_kg, 0.001);
        $this->assertDatabaseHas('butcher_inventory_adjustments', [
            'batch_id' => $batch->id,
            'stock_count_line_id' => $line->id,
        ]);
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
