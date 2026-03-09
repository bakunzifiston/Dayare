<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Demand;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use Illuminate\Database\Seeder;

/**
 * Seed demands for testing: CRUD, dashboard stats, filtering, status workflows.
 * Rwanda context: client names, RWF context (quantity + unit kg/heads).
 * Some demands are fulfilled and linked to delivery confirmations.
 */
class DemandSeeder extends Seeder
{
    private const RWANDA_CLIENT_NAMES = [
        'Umuriro Butchery',
        'Kigali Heights Restaurant',
        'Nyabugogo Market Stall',
        'Kayonza Distributor Ltd',
        'Restaurant Chez Marie',
        'Gasabo Fresh Meats',
    ];

    public function run(): void
    {
        $businesses = \App\Models\Business::has('facilities')->get();
        if ($businesses->isEmpty()) {
            $this->command?->warn('No businesses with facilities. Run TestDataSeeder first.');
            return;
        }

        $deliveries = DeliveryConfirmation::with('transportTrip')->get();
        $speciesOptions = \App\Models\Species::active()->pluck('name')->toArray();
        $speciesOptions = ! empty($speciesOptions) ? $speciesOptions : ['Cattle', 'Goat', 'Sheep'];
        $unitCode = \App\Models\Unit::active()->value('code') ?: 'kg';

        $year = date('Y');
        $last = Demand::where('demand_number', 'like', 'DEM-' . $year . '-%')->orderByDesc('id')->first();
        $demandNumber = $last ? (int) substr($last->demand_number, -4) + 1 : 1;
        if ($demandNumber < 1) {
            $demandNumber = 1;
        }

        foreach ($businesses as $business) {
            $facilities = $business->facilities()->whereIn('facility_type', [Facility::TYPE_BUTCHERY, Facility::TYPE_STORAGE])->get();
            $clients = Client::where('business_id', $business->id)->where('is_active', true)->get();
            $contracts = \App\Models\Contract::where('business_id', $business->id)->where('status', \App\Models\Contract::STATUS_ACTIVE)->get();

            $destinationFacility = $facilities->first();
            $client = $clients->first();
            $contract = $contracts->where('contract_category', \App\Models\Contract::CATEGORY_CUSTOMER)->first();

            // Draft
            $this->createDemand($business, $demandNumber++, Demand::STATUS_DRAFT, $destinationFacility?->id, $client?->id, $contract?->id, null, $speciesOptions, $unitCode);
            // Confirmed
            $this->createDemand($business, $demandNumber++, Demand::STATUS_CONFIRMED, $destinationFacility?->id, $client?->id, null, null, $speciesOptions, $unitCode);
            // In progress (inline client)
            $this->createDemand($business, $demandNumber++, Demand::STATUS_IN_PROGRESS, null, null, null, [
                'client_name' => self::RWANDA_CLIENT_NAMES[array_rand(self::RWANDA_CLIENT_NAMES)],
                'client_country' => 'Rwanda',
                'client_contact' => '+250788' . random_int(100000, 999999),
            ], $speciesOptions, $unitCode);
            // Fulfilled (link to a delivery if available for this business)
            $deliveryForBusiness = $deliveries->filter(function ($d) use ($business) {
                $trip = $d->transportTrip;
                if (! $trip || ! $trip->originFacility) {
                    return false;
                }
                return $trip->originFacility->business_id === $business->id;
            })->first();
            $this->createDemand($business, $demandNumber++, $deliveryForBusiness ? Demand::STATUS_FULFILLED : Demand::STATUS_CONFIRMED, $destinationFacility?->id, $client?->id, null, null, $speciesOptions, $unitCode, $deliveryForBusiness?->id);
            // Cancelled
            $this->createDemand($business, $demandNumber++, Demand::STATUS_CANCELLED, $destinationFacility?->id, null, null, null, $speciesOptions, $unitCode);
        }

        $this->command?->info('Demands seeded (Rwanda, all statuses, fulfilled links).');
    }

    private function createDemand(
        \App\Models\Business $business,
        int $num,
        string $status,
        ?int $destinationFacilityId,
        ?int $clientId,
        ?int $contractId,
        ?array $inlineClient,
        array $speciesOptions,
        string $unitCode,
        ?int $fulfilledByDeliveryId = null
    ): void {
        $title = 'Order #' . $num . ' — ' . ($inlineClient['client_name'] ?? 'Rwanda client');
        $demandNumber = 'DEM-' . date('Y') . '-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);

        $data = [
            'business_id' => $business->id,
            'demand_number' => $demandNumber,
            'title' => $title,
            'destination_facility_id' => $destinationFacilityId,
            'client_id' => $clientId,
            'contract_id' => $contractId,
            'species' => $speciesOptions[array_rand($speciesOptions)],
            'product_description' => 'Beef cuts, fresh',
            'quantity' => (string) rand(50, 500),
            'quantity_unit' => $unitCode,
            'requested_delivery_date' => now()->addDays(rand(3, 14)),
            'status' => $status,
            'notes' => 'RWF value — Rwanda.',
        ];

        if ($inlineClient) {
            $data['client_name'] = $inlineClient['client_name'] ?? null;
            $data['client_country'] = $inlineClient['client_country'] ?? null;
            $data['client_contact'] = $inlineClient['client_contact'] ?? null;
        }
        if ($fulfilledByDeliveryId) {
            $data['fulfilled_by_delivery_id'] = $fulfilledByDeliveryId;
        }

        Demand::firstOrCreate(
            ['business_id' => $business->id, 'demand_number' => $demandNumber],
            $data
        );
    }
}
