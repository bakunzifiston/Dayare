<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use App\Models\TransportTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class TransportTripExternalDestinationTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_can_record_trip_with_external_destination(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_name' => 'Nairobi Cold Store',
                'destination_country' => 'KE',
                'destination_address' => 'Industrial Area',
                'vehicle_plate_number' => 'RAB 999X',
                'driver_name' => 'Export Driver',
                'departure_date' => now()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertRedirect();

        $trip = TransportTrip::query()->latest('id')->first();
        $this->assertNotNull($trip);
        $this->assertNull($trip->destination_facility_id);
        $this->assertSame('Nairobi Cold Store', $trip->destination_name);
        $this->assertTrue($trip->isExternalDestination());
    }

    public function test_external_destination_requires_name(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'vehicle_plate_number' => 'RAB 999X',
                'driver_name' => 'Export Driver',
                'departure_date' => now()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertSessionHasErrors('destination_name');
    }
}
