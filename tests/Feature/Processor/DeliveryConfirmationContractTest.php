<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\DeliveryConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class DeliveryConfirmationContractTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_contracts_endpoint_returns_only_client_contracts(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $client = Client::factory()->create(['business_id' => $fixture['business']->id, 'is_active' => true]);
        $otherClient = Client::factory()->create(['business_id' => $fixture['business']->id, 'is_active' => true]);
        $contract = $this->createCustomerContract($fixture['business'], $client);
        $this->createCustomerContract($fixture['business'], $otherClient);

        $response = $this->actingAs($fixture['user'])
            ->getJson(route('delivery-confirmations.contracts', ['client_id' => $client->id]));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $contract->id);
    }

    public function test_store_persists_contract_id(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $client = Client::factory()->create(['business_id' => $fixture['business']->id, 'is_active' => true]);
        $contract = $this->createCustomerContract($fixture['business'], $client);

        $this->actingAs($fixture['user'])
            ->post(route('delivery-confirmations.store'), [
                'transport_trip_id' => $fixture['trip']->id,
                'client_id' => $client->id,
                'contract_id' => $contract->id,
                'received_quantity' => 10,
                'received_unit' => 'kg',
                'received_date' => now()->toDateString(),
                'receiver_name' => 'External Receiver',
                'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
            ])
            ->assertRedirect(route('delivery-confirmations.index'));

        $this->assertDatabaseHas('delivery_confirmations', [
            'transport_trip_id' => $fixture['trip']->id,
            'contract_id' => $contract->id,
            'received_unit' => 'kg',
        ]);
    }
}
