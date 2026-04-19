<?php

namespace App\Repositories\Logistics;

use App\Models\LogisticsTrip;
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
            ->with(['vehicle', 'driver', 'order', 'originLocation', 'destinationLocation', 'complianceDocuments', 'invoice'])
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
            ->with(['vehicle', 'driver', 'order', 'originLocation', 'destinationLocation', 'invoice'])
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
        return (int) LogisticsTrip::query()
            ->where('order_id', $orderId)
            ->whereIn('status', LogisticsTrip::ACTIVE_STATUSES)
            ->sum('allocated_weight_kg');
    }

    public function deliveredQuantityForOrder(int $orderId): int
    {
        return (int) LogisticsTrip::query()
            ->where('order_id', $orderId)
            ->where('status', LogisticsTrip::STATUS_COMPLETED)
            ->sum(DB::raw('COALESCE(delivered_weight_kg, 0)'));
    }
}
