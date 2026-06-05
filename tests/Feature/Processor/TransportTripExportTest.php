<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use App\Models\TransportTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class TransportTripExportTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_csv_export_includes_trip_columns(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $response = $this->actingAs($fixture['user'])
            ->get(route('transport-trips.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Trip ID', $response->streamedContent());
        $this->assertStringContainsString($fixture['trip']->vehicle_plate_number, $response->streamedContent());
    }

    public function test_status_filter_limits_export_rows(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        TransportTrip::query()->create([
            'certificate_id' => $fixture['certificate']->id,
            'origin_facility_id' => $fixture['origin']->id,
            'destination_facility_id' => $fixture['destination']->id,
            'vehicle_plate_number' => 'RAB 999Z',
            'driver_name' => 'Other',
            'departure_date' => now()->toDateString(),
            'status' => TransportTrip::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($fixture['user'])
            ->get(route('transport-trips.export', [
                'format' => 'csv',
                'status' => TransportTrip::STATUS_IN_TRANSIT,
            ]));

        $content = $response->streamedContent();
        $this->assertStringContainsString($fixture['trip']->vehicle_plate_number, $content);
        $this->assertStringNotContainsString('RAB 999Z', $content);
    }

    public function test_export_forbidden_without_permission(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_OPERATIONS_MANAGER, asMember: true);

        $this->actingAs($fixture['user'])
            ->get(route('transport-trips.export', ['format' => 'csv']))
            ->assertForbidden();
    }
}
