<?php

namespace App\Services\Logistics;

use App\Models\LogisticsVehicle;
use App\Models\User;
use App\Repositories\Logistics\VehicleRepository;

class VehicleService
{
    public function __construct(
        private CompanyService $companies,
        private VehicleRepository $vehicles
    ) {}

    public function create(User $user, array $attributes): LogisticsVehicle
    {
        $company = $this->companies->requireAccessible($user, (int) $attributes['company_id']);
        $attributes['company_id'] = (int) $company->id;
        $capacityValue = (float) ($attributes['capacity_value'] ?? 0);
        $capacityUnit = (string) ($attributes['capacity_unit'] ?? '');

        if ($capacityUnit === LogisticsVehicle::CAPACITY_UNIT_HEADS) {
            $attributes['max_units'] = max(1, (int) ceil($capacityValue));
            $attributes['max_weight'] = null;
        } elseif ($capacityUnit === LogisticsVehicle::CAPACITY_UNIT_TONS) {
            $attributes['max_units'] = 1000000;
            $attributes['max_weight'] = $capacityValue * 1000;
        } else {
            $attributes['max_units'] = 1000000;
            $attributes['max_weight'] = $capacityValue;
        }
        $attributes['vehicle_features'] = array_values(array_unique($attributes['vehicle_features'] ?? []));

        return $this->vehicles->create($attributes);
    }
}
