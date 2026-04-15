<?php

namespace App\Repositories\Logistics;

use App\Models\LogisticsTrip;
use App\Models\LogisticsTripOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TripRepository
{
    public function create(array $attributes): LogisticsTrip
    {
        return LogisticsTrip::query()->create($attributes);
    }

    public function find(int $tripId): ?LogisticsTrip
    {
        return LogisticsTrip::query()
            ->with(['vehicle', 'driver', 'orders', 'tripOrders', 'complianceDocuments', 'invoice'])
            ->find($tripId);
    }

    public function save(LogisticsTrip $trip): void
    {
        $trip->save();
    }

    /** @return Collection<int, LogisticsTrip> */
    public function byCompany(int $companyId): Collection
    {
        return LogisticsTrip::query()
            ->where('company_id', $companyId)
            ->with(['vehicle', 'driver', 'orders', 'tripOrders', 'invoice'])
            ->latest('planned_departure')
            ->get();
    }

    public function hasActiveDriverTrip(int $driverId): bool
    {
        return LogisticsTrip::query()
            ->where('driver_id', $driverId)
            ->whereIn('status', LogisticsTrip::ACTIVE_STATUSES)
            ->exists();
    }

    public function hasActiveVehicleTrip(int $vehicleId): bool
    {
        return LogisticsTrip::query()
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', LogisticsTrip::ACTIVE_STATUSES)
            ->exists();
    }

    public function reservedQuantityForOrder(int $orderId): int
    {
        return (int) LogisticsTripOrder::query()
            ->where('order_id', $orderId)
            ->whereHas('trip', fn ($query) => $query->whereIn('status', LogisticsTrip::ACTIVE_STATUSES))
            ->sum('allocated_quantity');
    }

    public function deliveredQuantityForOrder(int $orderId): int
    {
        return (int) LogisticsTripOrder::query()
            ->where('order_id', $orderId)
            ->whereHas('trip', fn ($query) => $query->where('status', LogisticsTrip::STATUS_DELIVERED))
            ->sum(DB::raw('COALESCE(delivered_quantity, 0)'));
    }

    public function syncOrders(LogisticsTrip $trip, array $syncPayload): void
    {
        $trip->orders()->sync($syncPayload);
    }

    /** @return Collection<int, LogisticsTripOrder> */
    public function tripOrders(int $tripId): Collection
    {
        return LogisticsTripOrder::query()
            ->where('trip_id', $tripId)
            ->get();
    }
}

