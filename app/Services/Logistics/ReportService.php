<?php

namespace App\Services\Logistics;

use App\Models\LogisticsTrip;
use App\Models\User;
use App\Repositories\Logistics\InvoiceRepository;
use App\Repositories\Logistics\TripRepository;

class ReportService
{
    public function __construct(
        private CompanyService $companies,
        private TripRepository $trips,
        private InvoiceRepository $invoices
    ) {}

    /** @return array<string, float|int> */
    public function summary(User $user, int $companyId): array
    {
        $this->companies->requireAccessible($user, $companyId);
        $trips = $this->trips->byCompany($companyId);
        $tripIds = $trips->pluck('id')->all();
        $invoices = $this->invoices->byTripIds($tripIds);

        $delivered = $trips->where('status', LogisticsTrip::STATUS_COMPLETED);
        $onTime = $delivered->filter(fn ($trip) => $trip->actual_arrival !== null && $trip->actual_arrival->lte($trip->planned_arrival))->count();
        $revenue = (float) $invoices->sum('total_amount');
        $revenuePerTrip = $trips->count() > 0 ? round($revenue / $trips->count(), 2) : 0.0;

        $allocated = (int) $trips->sum('allocated_weight_kg');
        $loss = (int) $trips->sum('loss_weight_kg');
        $lossRate = $allocated > 0 ? round(($loss / $allocated) * 100, 2) : 0.0;

        $durations = $trips->whereNotNull('actual_departure')->whereNotNull('actual_arrival')
            ->map(fn ($trip) => $trip->actual_departure->diffInMinutes($trip->actual_arrival));

        return [
            'total_trips' => (int) $trips->count(),
            'on_time_delivery_rate' => $delivered->count() > 0 ? round(($onTime / $delivered->count()) * 100, 2) : 0.0,
            'vehicle_utilization' => $trips->count() > 0 ? round(($trips->pluck('vehicle_id')->unique()->count() / max(1, $trips->count())) * 100, 2) : 0.0,
            'revenue_per_trip' => $revenuePerTrip,
            'loss_rate' => $lossRate,
            'average_delivery_time' => $durations->isNotEmpty() ? round((float) $durations->avg(), 2) : 0.0,
        ];
    }
}
