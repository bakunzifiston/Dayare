<?php

namespace App\Repositories\Logistics;

use App\Models\Business;
use App\Models\LogisticsCompany;
use App\Models\User;
use Illuminate\Support\Collection;

class CompanyRepository
{
    public function create(array $attributes): LogisticsCompany
    {
        return LogisticsCompany::query()->create($attributes);
    }

    public function findForUser(int $companyId, User $user): ?LogisticsCompany
    {
        $logisticsBusinessIds = Business::query()
            ->where('type', Business::TYPE_LOGISTICS)
            ->whereIn('id', $user->accessibleBusinessIds())
            ->pluck('id');

        return LogisticsCompany::query()
            ->whereKey($companyId)
            ->whereIn('business_id', $logisticsBusinessIds)
            ->first();
    }

    /** @return Collection<int, LogisticsCompany> */
    public function listForUser(User $user): Collection
    {
        $logisticsBusinessIds = Business::query()
            ->where('type', Business::TYPE_LOGISTICS)
            ->whereIn('id', $user->accessibleBusinessIds())
            ->pluck('id');

        return LogisticsCompany::query()
            ->whereIn('business_id', $logisticsBusinessIds)
            ->orderBy('name')
            ->get();
    }
}

