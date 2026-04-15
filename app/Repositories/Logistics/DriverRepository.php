<?php

namespace App\Repositories\Logistics;

use App\Models\LogisticsDriver;
use Illuminate\Support\Collection;

class DriverRepository
{
    public function create(array $attributes): LogisticsDriver
    {
        return LogisticsDriver::query()->create($attributes);
    }

    public function find(int $driverId): ?LogisticsDriver
    {
        return LogisticsDriver::query()->find($driverId);
    }

    /** @return Collection<int, LogisticsDriver> */
    public function byCompany(int $companyId): Collection
    {
        return LogisticsDriver::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }
}

