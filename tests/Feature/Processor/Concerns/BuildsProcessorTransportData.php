<?php

namespace Tests\Feature\Processor\Concerns;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\TransportTrip;
use App\Models\User;

trait BuildsProcessorTransportData
{
    /**
     * @return array{user: User, business: Business, origin: Facility, destination: Facility, certificate: Certificate, trip: TransportTrip}
     */
    protected function createProcessorTransportFixture(?string $role = null, bool $asMember = false): array
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create([
            'type' => Business::TYPE_PROCESSOR,
        ]);
        $user = $asMember ? User::factory()->create() : $owner;
        $user->setActiveProcessorBusinessId($business->id);

        if ($role !== null) {
            BusinessUser::query()->create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'role' => $role,
            ]);
        }

        $origin = Facility::factory()->create(['business_id' => $business->id]);
        $destination = Facility::factory()->create(['business_id' => $business->id]);
        $inspector = Inspector::factory()->create(['facility_id' => $origin->id]);

        $certificate = Certificate::query()->create([
            'inspector_id' => $inspector->id,
            'facility_id' => $origin->id,
            'certificate_number' => 'CERT-'.uniqid(),
            'issued_at' => now()->toDateString(),
            'status' => Certificate::STATUS_ACTIVE,
        ]);

        $trip = TransportTrip::query()->create([
            'certificate_id' => $certificate->id,
            'origin_facility_id' => $origin->id,
            'destination_facility_id' => null,
            'destination_name' => $destination->facility_name,
            'vehicle_plate_number' => 'RAB 123A',
            'driver_name' => 'Test Driver',
            'driver_phone' => '+250788000000',
            'departure_date' => now()->toDateString(),
            'status' => TransportTrip::STATUS_IN_TRANSIT,
        ]);

        return compact('user', 'business', 'origin', 'destination', 'certificate', 'trip');
    }

    protected function createDeliveryForTrip(
        TransportTrip $trip,
        Facility $origin,
        ?int $clientId = null,
        ?int $contractId = null,
        ?string $receiverCountry = null,
    ): DeliveryConfirmation {
        $international = $receiverCountry && strtoupper($receiverCountry) !== strtoupper((string) config('processor.domestic_country', 'RW'));

        return DeliveryConfirmation::query()->create([
            'transport_trip_id' => $trip->id,
            'receiving_facility_id' => null,
            'client_id' => $clientId,
            'contract_id' => $contractId,
            'received_quantity' => 100,
            'received_unit' => 'kg',
            'received_date' => now()->toDateString(),
            'receiver_name' => $trip->destination_name ?? $trip->destination_display ?? 'Receiver',
            'receiver_country' => $receiverCountry,
            'receiver_address' => $international ? '123 Export St' : null,
            'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
        ]);
    }

    protected function createCustomerContract(Business $business, Client $client): Contract
    {
        return Contract::query()->create([
            'business_id' => $business->id,
            'client_id' => $client->id,
            'contract_category' => Contract::CATEGORY_CUSTOMER,
            'type' => Contract::TYPE_SERVICE_AGREEMENT,
            'contract_number' => 'CUST-'.uniqid(),
            'title' => 'Test customer contract',
            'status' => Contract::STATUS_ACTIVE,
            'start_date' => now()->toDateString(),
        ]);
    }

    protected function createOpenDemand(Business $business, Client $client): Demand
    {
        return Demand::query()->create([
            'business_id' => $business->id,
            'demand_number' => 'DEM-'.uniqid(),
            'title' => 'Test demand',
            'client_id' => $client->id,
            'species' => 'cattle',
            'status' => Demand::STATUS_IN_PROGRESS,
            'quantity' => 50,
            'quantity_unit' => 'kg',
            'requested_delivery_date' => now()->addWeek()->toDateString(),
        ]);
    }
}
