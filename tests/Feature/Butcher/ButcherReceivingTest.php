<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\ButcherDelivery;
use App\Models\ButcherDeliveryLine;
use App\Models\ButcherDeliveryRejection;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use App\Models\ButcherOutlet;
use App\Models\ButcherPurchaseOrder;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
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

        $this->assertDatabaseHas('butcher_delivery_lines', [
            'delivery_id' => $delivery->id,
            'outcome' => ButcherDeliveryLine::OUTCOME_ACCEPTED,
            'accepted_weight_kg' => 48.25,
        ]);

        $this->assertDatabaseHas('butcher_inventory_movements', [
            'business_id' => $this->business->id,
            'batch_id' => $batch->id,
            'type' => ButcherInventoryMovement::TYPE_RECEIPT,
        ]);
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
        $this->assertDatabaseHas('butcher_delivery_lines', [
            'delivery_id' => $delivery->id,
            'outcome' => ButcherDeliveryLine::OUTCOME_REJECTED,
        ]);
    }

    public function test_multi_line_mixed_outcomes_create_batches_rejections_and_ledger(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.receiving.store'), [
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'lines' => [
                    [
                        'meat_type' => ButcherDelivery::MEAT_BEEF,
                        'received_weight_kg' => 40,
                        'unit_cost' => 3000,
                        'outcome' => ButcherDeliveryLine::OUTCOME_ACCEPTED,
                        'accepted_weight_kg' => 40,
                        'rejected_weight_kg' => 0,
                    ],
                    [
                        'meat_type' => ButcherDelivery::MEAT_GOAT,
                        'received_weight_kg' => 20,
                        'unit_cost' => 4000,
                        'outcome' => ButcherDeliveryLine::OUTCOME_PARTIALLY_ACCEPTED,
                        'accepted_weight_kg' => 12,
                        'rejected_weight_kg' => 8,
                    ],
                    [
                        'meat_type' => ButcherDelivery::MEAT_PORK,
                        'received_weight_kg' => 10,
                        'unit_cost' => 2500,
                        'outcome' => ButcherDeliveryLine::OUTCOME_REJECTED,
                        'accepted_weight_kg' => 0,
                        'rejected_weight_kg' => 10,
                    ],
                ],
            ])
            ->assertRedirect();

        $delivery = ButcherDelivery::query()->first();
        $this->assertNotNull($delivery);
        $this->assertSame(3, $delivery->lines()->count());
        $this->assertSame(2, ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->count());
        $this->assertSame(2, ButcherDeliveryRejection::query()->where('delivery_id', $delivery->id)->count());
        $this->assertSame(2, ButcherInventoryMovement::query()
            ->where('business_id', $this->business->id)
            ->where('type', ButcherInventoryMovement::TYPE_RECEIPT)
            ->count());

        // Accepted cost only: 40*3000 + 12*4000 = 168000
        $this->assertSame(168000.0, (float) $delivery->total_cost);
        $this->assertSame(ButcherDelivery::CONDITION_FAIR, $delivery->condition);
    }

    public function test_line_weight_mismatch_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->from(route('butcher.receiving.create'))
            ->post(route('butcher.receiving.store'), [
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'lines' => [
                    [
                        'meat_type' => ButcherDelivery::MEAT_BEEF,
                        'received_weight_kg' => 10,
                        'unit_cost' => 3000,
                        'outcome' => ButcherDeliveryLine::OUTCOME_PARTIALLY_ACCEPTED,
                        'accepted_weight_kg' => 6,
                        'rejected_weight_kg' => 5,
                    ],
                ],
            ])
            ->assertRedirect(route('butcher.receiving.create'))
            ->assertSessionHasErrors('lines.0.accepted_weight_kg');

        $this->assertDatabaseCount('butcher_deliveries', 0);
    }

    public function test_post_rolls_back_when_batch_creation_fails_midway(): void
    {
        $real = app(ButcherStorageService::class);
        $calls = 0;
        $mock = Mockery::mock(ButcherStorageService::class);
        $mock->shouldReceive('createBatchFromDelivery')
            ->andReturnUsing(function (...$args) use ($real, &$calls) {
                $calls++;
                if ($calls >= 2) {
                    throw new \RuntimeException('Forced failure on second batch');
                }

                return $real->createBatchFromDelivery(...$args);
            });
        $this->app->instance(ButcherStorageService::class, $mock);
        $this->app->forgetInstance(\App\Services\Butcher\ButcherProcurementService::class);

        $service = app(\App\Services\Butcher\ButcherProcurementService::class);

        try {
            $service->receiveDelivery($this->business, [
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'lines' => [
                    [
                        'meat_type' => ButcherDelivery::MEAT_BEEF,
                        'received_weight_kg' => 10,
                        'unit_cost' => 3000,
                        'outcome' => ButcherDeliveryLine::OUTCOME_ACCEPTED,
                        'accepted_weight_kg' => 10,
                        'rejected_weight_kg' => 0,
                    ],
                    [
                        'meat_type' => ButcherDelivery::MEAT_GOAT,
                        'received_weight_kg' => 10,
                        'unit_cost' => 3000,
                        'outcome' => ButcherDeliveryLine::OUTCOME_ACCEPTED,
                        'accepted_weight_kg' => 10,
                        'rejected_weight_kg' => 0,
                    ],
                    [
                        'meat_type' => ButcherDelivery::MEAT_PORK,
                        'received_weight_kg' => 10,
                        'unit_cost' => 3000,
                        'outcome' => ButcherDeliveryLine::OUTCOME_REJECTED,
                        'accepted_weight_kg' => 0,
                        'rejected_weight_kg' => 10,
                    ],
                ],
            ], $this->user);
            $this->assertTrue(false, 'Expected forced batch failure to abort the post.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Forced failure on second batch', $e->getMessage());
        }

        $this->assertSame(0, DB::table('butcher_deliveries')->count());
        $this->assertSame(0, DB::table('butcher_delivery_lines')->count());
        $this->assertSame(0, DB::table('butcher_inventory_batches')->count());
        $this->assertSame(0, DB::table('butcher_delivery_rejections')->count());
        $this->assertSame(0, DB::table('butcher_inventory_movements')->count());
    }

    public function test_cashier_cannot_post_delivery(): void
    {
        $cashier = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id' => $cashier->id,
            'role' => BusinessUser::ROLE_BUTCHER_CASHIER,
        ]);

        $this->actingAs($cashier)
            ->post(route('butcher.receiving.store'), [
                'supplier_id' => $this->supplier->id,
                'outlet_id' => $this->outlet->id,
                'lines' => [
                    [
                        'meat_type' => ButcherDelivery::MEAT_BEEF,
                        'received_weight_kg' => 10,
                        'unit_cost' => 3000,
                        'outcome' => ButcherDeliveryLine::OUTCOME_ACCEPTED,
                        'accepted_weight_kg' => 10,
                        'rejected_weight_kg' => 0,
                    ],
                ],
            ])
            ->assertForbidden();
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

    public function test_existing_deliveries_are_backfilled_into_lines_on_migrate(): void
    {
        // RefreshDatabase already ran migrations (including backfill on empty set).
        // Seed a pre-line delivery shape and re-run the backfill SQL used by the migration.
        $deliveryId = DB::table('butcher_deliveries')->insertGetId([
            'business_id' => $this->business->id,
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outlet->id,
            'delivery_number' => 'DEL-BF-0001',
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 15.5,
            'unit_cost_per_kg' => 2000,
            'total_cost' => 31000,
            'condition' => ButcherDelivery::CONDITION_GOOD,
            'received_at' => now(),
            'received_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, DB::table('butcher_delivery_lines')->where('delivery_id', $deliveryId)->count());

        $migration = require database_path('migrations/2026_09_10_140000_create_butcher_delivery_lines_and_relax_uniques.php');
        $method = new \ReflectionMethod($migration, 'backfillDeliveryLines');
        $method->setAccessible(true);
        $method->invoke($migration);

        $this->assertDatabaseHas('butcher_delivery_lines', [
            'delivery_id' => $deliveryId,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'outcome' => ButcherDeliveryLine::OUTCOME_ACCEPTED,
            'accepted_weight_kg' => 15.5,
            'rejected_weight_kg' => 0,
        ]);
    }
}
