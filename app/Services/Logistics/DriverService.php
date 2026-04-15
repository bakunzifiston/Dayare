<?php

namespace App\Services\Logistics;

use App\Models\LogisticsDriver;
use App\Models\User;
use App\Repositories\Logistics\DriverRepository;

class DriverService
{
    public function __construct(
        private CompanyService $companies,
        private DriverRepository $drivers
    ) {}

    public function create(User $user, array $attributes): LogisticsDriver
    {
        $company = $this->companies->requireAccessible($user, (int) $attributes['company_id']);
        $attributes['company_id'] = (int) $company->id;

        return $this->drivers->create($attributes);
    }
}

