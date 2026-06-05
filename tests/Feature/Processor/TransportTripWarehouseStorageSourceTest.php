<?php

namespace Tests\Feature\Processor;

use App\Models\Batch;
use App\Models\BusinessUser;
use App\Models\Certificate;
use App\Models\TransportTrip;
use App\Models\WarehouseStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class TransportTripWarehouseStorageSourceTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_trip_links_certificate_and_batch_from_released_storage(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $inspector = \App\Models\Inspector::factory()->create(['facility_id' => $fixture['origin']->id]);
        $batch = Batch::factory()->create(['inspector_id' => $inspector->id]);

        $storage = WarehouseStorage::query()->create([
            'warehouse_facility_id' => $fixture['origin']->id,
            'batch_id' => $batch->id,
            'certificate_id' => $fixture['certificate']->id,
            'entry_date' => now()->toDateString(),
            'quantity_stored' => 100,
            'quantity_unit' => 'kg',
            'status' => WarehouseStorage::STATUS_RELEASED,
            'released_date' => now()->toDateString(),
        ]);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'require_released_storage' => true,
                'warehouse_storage_id' => $storage->id,
                'certificate_id' => 99999,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_type' => 'facility',
                'destination_facility_id' => $fixture['destination']->id,
                'vehicle_plate_number' => 'RAB 100A',
                'driver_name' => 'Driver',
                'departure_date' => now()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertRedirect();

        $trip = TransportTrip::query()->latest('id')->first();
        $this->assertSame($storage->id, $trip->warehouse_storage_id);
        $this->assertSame($fixture['certificate']->id, $trip->certificate_id);
        $this->assertSame($batch->id, $trip->batch_id);
    }
}
