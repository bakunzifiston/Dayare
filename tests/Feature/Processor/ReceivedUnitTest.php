<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class ReceivedUnitTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_invalid_received_unit_rejected(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->post(route('delivery-confirmations.store'), [
                'transport_trip_id' => $fixture['trip']->id,
                'receiving_facility_id' => $fixture['origin']->id,
                'received_quantity' => 5,
                'received_unit' => 'invalid_unit',
                'received_date' => now()->toDateString(),
                'receiver_name' => 'Receiver',
                'confirmation_status' => 'pending',
            ])
            ->assertSessionHasErrors('received_unit');
    }

    public function test_default_received_unit_is_units_when_omitted(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->post(route('delivery-confirmations.store'), [
                'transport_trip_id' => $fixture['trip']->id,
                'receiving_facility_id' => $fixture['origin']->id,
                'received_quantity' => 5,
                'received_date' => now()->toDateString(),
                'receiver_name' => 'Receiver',
                'confirmation_status' => 'pending',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('delivery_confirmations', [
            'transport_trip_id' => $fixture['trip']->id,
            'received_unit' => 'units',
        ]);
    }
}
