<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use App\Models\Demand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class DestroyWithLinkedDemandTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_deleting_confirmation_unlinks_demand_and_resets_status(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $client = \App\Models\Client::factory()->create(['business_id' => $fixture['business']->id, 'is_active' => true]);
        $delivery = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], $client->id);
        $demand = $this->createOpenDemand($fixture['business'], $client);
        $demand->update([
            'fulfilled_by_delivery_id' => $delivery->id,
            'status' => Demand::STATUS_FULFILLED,
        ]);

        $this->actingAs($fixture['user'])
            ->delete(route('delivery-confirmations.destroy', $delivery))
            ->assertRedirect(route('delivery-confirmations.index'));

        $demand->refresh();
        $this->assertNull($demand->fulfilled_by_delivery_id);
        $this->assertSame(Demand::STATUS_IN_PROGRESS, $demand->status);
    }
}
