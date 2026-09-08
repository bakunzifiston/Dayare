<?php
namespace Tests\Feature\Processor;
use App\Models\BusinessUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class DeliveryConfirmationCreatePageTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_create_page_renders_with_trip(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->get(route('delivery-confirmations.create', ['transport_trip_id' => $fixture['trip']->id]))
            ->assertOk()
            ->assertSee('transport_trip_id', false)
            ->assertSee('receiver_name', false)
            ->assertSee(__('Save confirmation'), false);
    }

    public function test_create_page_renders_without_trips(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        // Confirm the only trip so create list is empty
        \App\Models\DeliveryConfirmation::create([
            'transport_trip_id' => $fixture['trip']->id,
            'received_quantity' => 1,
            'received_unit' => 'kg',
            'received_date' => now()->toDateString(),
            'receiver_name' => 'X',
            'confirmation_status' => 'pending',
        ]);

        $this->actingAs($fixture['user'])
            ->get(route('delivery-confirmations.create'))
            ->assertOk();
    }
}
