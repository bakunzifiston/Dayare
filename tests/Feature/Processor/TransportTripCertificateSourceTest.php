<?php

namespace Tests\Feature\Processor;

use App\Models\BusinessUser;
use App\Models\Certificate;
use App\Models\TransportTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class TransportTripCertificateSourceTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_trip_links_certificate_without_warehouse_storage(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_name' => $fixture['destination']->facility_name,
                'vehicle_plate_number' => 'RAB 100A',
                'driver_name' => 'Driver',
                'departure_date' => now()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertRedirect();

        $trip = TransportTrip::query()->latest('id')->first();
        $this->assertSame($fixture['certificate']->id, $trip->certificate_id);
        $this->assertNull($trip->warehouse_storage_id);
    }

    public function test_create_form_prefills_certificate_from_query_string(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->get(route('transport-trips.create', ['certificate_id' => $fixture['certificate']->id]))
            ->assertOk()
            ->assertSee($fixture['certificate']->certificate_number, false);
    }

    public function test_trip_prefills_driver_details_from_certificate_pdf_details(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $fixture['certificate']->update([
            'pdf_details' => [
                'vehicle_plate_number' => 'RAB 777C',
                'driver_name' => 'Jean Transport',
                'transporter_phone' => '+250788111222',
            ],
        ]);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_name' => $fixture['destination']->facility_name,
                'departure_date' => now()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertRedirect();

        $trip = TransportTrip::query()->latest('id')->first();
        $this->assertSame('RAB 777C', $trip->vehicle_plate_number);
        $this->assertSame('Jean Transport', $trip->driver_name);
        $this->assertSame('+250788111222', $trip->driver_phone);
    }

    public function test_locked_driver_name_from_certificate_overrides_submitted_value(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $fixture['certificate']->update([
            'pdf_details' => [
                'driver_name' => 'Jean Transport',
                'vehicle_plate_number' => 'RAB 777C',
            ],
        ]);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_name' => $fixture['destination']->facility_name,
                'vehicle_plate_number' => 'RAB 777C',
                'driver_name' => 'Different Driver',
                'departure_date' => now()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertRedirect();

        $trip = TransportTrip::query()->latest('id')->first();
        $this->assertSame('Jean Transport', $trip->driver_name);
        $this->assertSame('RAB 777C', $trip->vehicle_plate_number);
    }

    public function test_trip_syncs_transporter_details_back_to_certificate(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_name' => $fixture['destination']->facility_name,
                'vehicle_plate_number' => 'RAB 555B',
                'driver_name' => 'Paul Driver',
                'driver_phone' => '+250788333444',
                'departure_date' => now()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertRedirect();

        $fixture['certificate']->refresh();
        $this->assertSame('RAB 555B', $fixture['certificate']->pdf_details['vehicle_plate_number'] ?? null);
        $this->assertSame('Paul Driver', $fixture['certificate']->pdf_details['driver_name'] ?? null);
        $this->assertSame('+250788333444', $fixture['certificate']->pdf_details['transporter_phone'] ?? null);
    }

    public function test_rejects_expired_certificate_for_transport(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $fixture['certificate']->update([
            'status' => Certificate::STATUS_ACTIVE,
            'expiry_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_name' => $fixture['destination']->facility_name,
                'vehicle_plate_number' => 'RAB 100A',
                'driver_name' => 'Driver',
                'departure_date' => now()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertSessionHasErrors('certificate_id');
    }

    public function test_rejects_departure_before_certificate_issue_date(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $fixture['certificate']->update([
            'issued_at' => now()->toDateString(),
            'expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_name' => $fixture['destination']->facility_name,
                'vehicle_plate_number' => 'RAB 100A',
                'driver_name' => 'Driver',
                'departure_date' => now()->subDay()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertSessionHasErrors('departure_date');
    }

    public function test_locked_departure_date_from_certificate_overrides_submitted_value(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $certificateDeparture = now();
        $fixture['certificate']->update([
            'issued_at' => $certificateDeparture->copy()->subDay()->toDateString(),
            'expiry_date' => $certificateDeparture->copy()->addMonth()->toDateString(),
            'pdf_details' => [
                'departure_time' => $certificateDeparture->format('d/m/Y H:i'),
            ],
        ]);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_name' => $fixture['destination']->facility_name,
                'vehicle_plate_number' => 'RAB 100A',
                'driver_name' => 'Driver',
                'departure_date' => $certificateDeparture->copy()->addDay()->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertRedirect();

        $trip = TransportTrip::query()->latest('id')->first();
        $this->assertSame($certificateDeparture->toDateString(), $trip->departure_date->toDateString());
    }

    public function test_accepts_departure_matching_certificate_departure_time(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $departure = now();
        $fixture['certificate']->update([
            'issued_at' => $departure->copy()->subDay()->toDateString(),
            'expiry_date' => $departure->copy()->addMonth()->toDateString(),
            'pdf_details' => [
                'departure_time' => $departure->format('d/m/Y H:i'),
            ],
        ]);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_name' => $fixture['destination']->facility_name,
                'vehicle_plate_number' => 'RAB 100A',
                'driver_name' => 'Driver',
                'departure_date' => $departure->toDateString(),
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertRedirect();
    }

    public function test_trip_prefills_destination_and_departure_from_certificate(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER);
        $departure = now();
        $fixture['certificate']->update([
            'issued_at' => $departure->copy()->subDay()->toDateString(),
            'expiry_date' => $departure->copy()->addMonth()->toDateString(),
            'pdf_details' => [
                'vehicle_plate_number' => 'RAB 888D',
                'driver_name' => 'Alice Driver',
                'transporter_phone' => '+250788999000',
                'departure_destination' => 'Border post Rusumo',
                'destination_country' => 'TZ',
                'destination_address' => 'Gate 2',
                'departure_time' => $departure->format('d/m/Y H:i'),
            ],
        ]);

        $this->actingAs($fixture['user'])
            ->get(route('transport-trips.create', ['certificate_id' => $fixture['certificate']->id]))
            ->assertOk()
            ->assertSee('Border post Rusumo', false)
            ->assertSee('TZ', false)
            ->assertSee('Gate 2', false)
            ->assertSee($departure->toDateString(), false);

        $this->actingAs($fixture['user'])
            ->post(route('transport-trips.store'), [
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'status' => TransportTrip::STATUS_PENDING,
            ])
            ->assertRedirect();

        $trip = TransportTrip::query()->latest('id')->first();
        $this->assertSame('RAB 888D', $trip->vehicle_plate_number);
        $this->assertSame('Alice Driver', $trip->driver_name);
        $this->assertSame('+250788999000', $trip->driver_phone);
        $this->assertSame('Border post Rusumo', $trip->destination_name);
        $this->assertSame('TZ', $trip->destination_country);
        $this->assertSame('Gate 2', $trip->destination_address);
        $this->assertSame($departure->toDateString(), $trip->departure_date->toDateString());
    }
}
