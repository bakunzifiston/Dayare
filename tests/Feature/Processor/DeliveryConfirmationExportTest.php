<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class DeliveryConfirmationExportTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_csv_export_includes_received_unit_and_external_label(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $client = \App\Models\Client::factory()->create(['business_id' => $fixture['business']->id, 'is_active' => true]);
        $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], $client->id);

        $response = $this->actingAs($fixture['user'])
            ->get(route('delivery-confirmations.export', ['format' => 'csv']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Received unit', $content);
        $this->assertStringContainsString('kg', $content);
        $this->assertStringContainsString('External', $content);
    }

    public function test_export_forbidden_without_permission(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_OPERATIONS_MANAGER, asMember: true);

        $this->actingAs($fixture['user'])
            ->get(route('delivery-confirmations.export', ['format' => 'csv']))
            ->assertForbidden();
    }
}
