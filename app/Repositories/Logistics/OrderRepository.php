<?php

namespace App\Repositories\Logistics;

use App\Models\LogisticsOrder;
use Illuminate\Support\Collection;

class OrderRepository
{
    public function create(array $attributes): LogisticsOrder
    {
        return LogisticsOrder::query()->create($attributes);
    }

    public function find(int $orderId): ?LogisticsOrder
    {
        return LogisticsOrder::query()->find($orderId);
    }

    public function save(LogisticsOrder $order): void
    {
        $order->save();
    }

    /** @return Collection<int, LogisticsOrder> */
    public function byCompany(int $companyId): Collection
    {
        return LogisticsOrder::query()
            ->where('company_id', $companyId)
            ->latest('requested_date')
            ->get();
    }

    /** @return Collection<int, LogisticsOrder> */
    public function approvedByCompanyAndIds(int $companyId, array $ids): Collection
    {
        return LogisticsOrder::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->where('status', LogisticsOrder::STATUS_APPROVED)
            ->get();
    }
}

