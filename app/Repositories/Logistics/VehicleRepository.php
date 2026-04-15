<?php

namespace App\Repositories\Logistics;

use App\Models\LogisticsVehicle;
use Illuminate\Support\Collection;

class VehicleRepository
{
    public function create(array $attributes): LogisticsVehicle
    {
        return LogisticsVehicle::query()->create($attributes);
    }

    public function find(int $vehicleId): ?LogisticsVehicle
    {
        return LogisticsVehicle::query()->find($vehicleId);
    }

    /** @return Collection<int, LogisticsVehicle> */
    public function byCompany(int $companyId): Collection
    {
        return LogisticsVehicle::query()
            ->where('company_id', $companyId)
            ->orderBy('plate_number')
            ->get();
    }
}

