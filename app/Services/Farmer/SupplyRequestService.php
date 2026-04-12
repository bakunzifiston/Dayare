<?php

namespace App\Services\Farmer;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Farm;
use App\Models\Livestock;
use App\Models\SupplyRequest;
use App\Models\User;
use App\Support\FarmerAnimalType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplyRequestService
{
    public function reject(SupplyRequest $supplyRequest, User $user): void
    {
        $this->assertFarmerOwnsRequest($supplyRequest, $user);

        if (! $supplyRequest->isPending()) {
            throw ValidationException::withMessages([
                'supply_request' => [__('Only pending requests can be rejected.')],
            ]);
        }

        $supplyRequest->update(['status' => SupplyRequest::STATUS_REJECTED]);
    }

    public function accept(SupplyRequest $supplyRequest, User $user, int $sourceFarmId): AnimalIntake
    {
        $this->assertFarmerOwnsRequest($supplyRequest, $user);

        if (! $supplyRequest->isPending()) {
            throw ValidationException::withMessages([
                'supply_request' => [__('Only pending requests can be accepted.')],
            ]);
        }

        $farmerIds = $user->accessibleFarmerBusinessIds();
        $farm = Farm::query()
            ->where('id', $sourceFarmId)
            ->whereIn('business_id', $farmerIds)
            ->firstOrFail();

        if ($farm->business_id !== (int) $supplyRequest->farmer_id) {
            throw ValidationException::withMessages([
                'farm_id' => [__('The selected farm does not belong to this supply request.')],
            ]);
        }

        return DB::transaction(function () use ($supplyRequest, $farm) {
            /** @var Livestock|null $livestock */
            $livestock = Livestock::query()
                ->where('farm_id', $farm->id)
                ->where('type', $supplyRequest->animal_type)
                ->lockForUpdate()
                ->first();

            if ($livestock === null) {
                throw ValidationException::withMessages([
                    'animal_type' => [__('No livestock of this type is registered for the selected farm.')],
                ]);
            }

            $qty = $supplyRequest->quantity_requested;

            if ($livestock->available_quantity < $qty) {
                throw ValidationException::withMessages([
                    'quantity' => [__('Insufficient available animals for this request.')],
                ]);
            }

            if ($livestock->healthy_quantity < $qty) {
                throw ValidationException::withMessages([
                    'quantity' => [__('Supply can only use healthy animals. Not enough healthy quantity for this request.')],
                ]);
            }

            $livestock->decrement('available_quantity', $qty);
            $livestock->decrement('healthy_quantity', $qty);
            $livestock->decrement('total_quantity', $qty);

            $farmerBusiness = Business::query()->findOrFail($supplyRequest->farmer_id);
            $facility = Facility::query()->findOrFail($supplyRequest->destination_facility_id);

            $intake = AnimalIntake::create([
                'facility_id' => $facility->id,
                'supply_request_id' => $supplyRequest->id,
                'farm_id' => $farm->id,
                'intake_date' => now()->toDateString(),
                'supplier_firstname' => (string) ($farmerBusiness->owner_first_name ?? ''),
                'supplier_lastname' => (string) ($farmerBusiness->owner_last_name ?? ''),
                'supplier_contact' => $farmerBusiness->contact_phone,
                'farm_name' => $farm->name,
                'farm_registration_number' => $farmerBusiness->registration_number,
                'country_id' => $farm->country_id,
                'province_id' => $farm->province_id,
                'district_id' => $farm->district_id,
                'sector_id' => $farm->sector_id,
                'cell_id' => $farm->cell_id,
                'village_id' => $farm->village_id,
                'species' => FarmerAnimalType::toIntakeSpecies($supplyRequest->animal_type),
                'number_of_animals' => $supplyRequest->quantity_requested,
                'status' => AnimalIntake::STATUS_RECEIVED,
                'animal_identification_numbers' => $supplyRequest->animal_type === FarmerAnimalType::POULTRY
                    ? __('Poultry (farmer supply)')
                    : null,
            ]);

            $supplyRequest->update([
                'status' => SupplyRequest::STATUS_FULFILLED,
                'source_farm_id' => $farm->id,
            ]);

            return $intake;
        });
    }

    private function assertFarmerOwnsRequest(SupplyRequest $supplyRequest, User $user): void
    {
        if (! $user->accessibleFarmerBusinessIds()->contains((int) $supplyRequest->farmer_id)) {
            abort(403);
        }
    }
}
