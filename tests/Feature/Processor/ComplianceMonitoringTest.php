<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use App\Models\Certificate;
use App\Models\TransportTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class ComplianceMonitoringTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_compliance_index_lists_missing_delivery_for_departed_trip(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_COMPLIANCE_OFFICER);
        $fixture['trip']->update([
            'departure_date' => now()->subDay()->toDateString(),
            'status' => TransportTrip::STATUS_IN_TRANSIT,
        ]);

        $response = $this->actingAs($fixture['user'])
            ->get(route('compliance.index'));

        $response->assertOk();
        $response->assertSee(__('Missing delivery confirmations'));
        $response->assertSee($fixture['trip']->vehicle_plate_number);
        $response->assertSee($fixture['certificate']->certificate_number);
    }

    public function test_compliance_index_lists_active_certificate_without_transport(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_COMPLIANCE_OFFICER);
        $fixture['trip']->delete();

        $response = $this->actingAs($fixture['user'])
            ->get(route('compliance.index'));

        $response->assertOk();
        $response->assertSee(__('Missing transport records'));
        $response->assertSee($fixture['certificate']->certificate_number);
    }

    public function test_compliance_index_shows_health_certificate_advisory_not_blocking_copy(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_COMPLIANCE_OFFICER);
        $fixture['trip']->delete();

        \App\Models\AnimalIntake::query()->create([
            'facility_id' => $fixture['origin']->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Jean',
            'supplier_lastname' => 'Supplier',
            'species' => 'cattle',
            'number_of_animals' => 3,
            'status' => \App\Models\AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'health_certificate_expiry_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($fixture['user'])
            ->get(route('compliance.index'));

        $response->assertOk();
        $response->assertSee(__('Animal intakes — health certificate advisory'));
        $response->assertSee(__('slaughter planning is not blocked'));
    }
}
