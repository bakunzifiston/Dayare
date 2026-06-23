<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use App\Models\DeliveryConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class DeliveryConfirmationTransportAlignmentTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_store_prefills_receiver_from_trip_destination(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->post(route('delivery-confirmations.store'), [
                'transport_trip_id' => $fixture['trip']->id,
                'received_quantity' => 10,
                'received_unit' => 'kg',
                'received_date' => now()->toDateString(),
                'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
            ])
            ->assertRedirect(route('delivery-confirmations.hub'));

        $this->assertDatabaseHas('delivery_confirmations', [
            'transport_trip_id' => $fixture['trip']->id,
            'receiver_name' => $fixture['destination']->facility_name,
            'receiving_facility_id' => null,
        ]);
    }

    public function test_store_rejects_receiver_name_that_does_not_match_trip(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->post(route('delivery-confirmations.store'), [
                'transport_trip_id' => $fixture['trip']->id,
                'received_quantity' => 10,
                'received_unit' => 'kg',
                'received_date' => now()->toDateString(),
                'receiver_name' => 'Wrong Receiver',
                'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
            ])
            ->assertSessionHasErrors('receiver_name');
    }

    public function test_store_rejects_receiving_facility_id(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->post(route('delivery-confirmations.store'), [
                'transport_trip_id' => $fixture['trip']->id,
                'receiving_facility_id' => $fixture['destination']->id,
                'received_quantity' => 10,
                'received_unit' => 'kg',
                'received_date' => now()->toDateString(),
                'receiver_name' => $fixture['destination']->facility_name,
                'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
            ])
            ->assertSessionHasErrors('receiving_facility_id');
    }
}
