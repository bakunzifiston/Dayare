<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class TraceabilityExportTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_traceability_pdf_generates_for_transport_manager(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $this->createDeliveryForTrip($fixture['trip'], $fixture['origin']);

        $response = $this->actingAs($fixture['user'])
            ->get(route('transport-trips.export.traceability'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_traceability_forbidden_for_operations_manager(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_OPERATIONS_MANAGER, asMember: true);

        $this->actingAs($fixture['user'])
            ->get(route('transport-trips.export.traceability'))
            ->assertForbidden();
    }
}
