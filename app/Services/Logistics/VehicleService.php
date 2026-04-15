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

        return $this->vehicles->create($attributes);
    }
}

