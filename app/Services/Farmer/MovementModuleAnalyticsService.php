<?php

namespace App\Services\Farmer;

use App\Models\MovementHistory;
use App\Models\MovementLog;
use App\Models\MovementPermit;
use App\Models\PermitRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MovementModuleAnalyticsService
{
    /** @param  Collection<int, int>  $farmerIds */
    public function metrics(Collection $farmerIds): array
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $permitQuery = MovementPermit::query()->whereIn('farmer_id', $farmerIds);
        $requestQuery = PermitRequest::query()->whereIn('farmer_id', $farmerIds);

        return [
            'requests_submitted_today' => (int) (clone $requestQuery)->whereDate('request_date', $today)->where('status', '!=', PermitRequest::STATUS_DRAFT)->count(),
            'pending_reviews' => (int) (clone $requestQuery)->whereIn('status', [PermitRequest::STATUS_SUBMITTED, PermitRequest::STATUS_UNDER_REVIEW])->count(),
            'approved_requests' => (int) (clone $requestQuery)->where('status', PermitRequest::STATUS_APPROVED)->count(),
            'active_permits' => (int) (clone $permitQuery)->whereIn('permit_status', [
                MovementPermit::STATUS_APPROVED,
                MovementPermit::STATUS_ISSUED,
                MovementPermit::STATUS_ACTIVE,
            ])->count(),
            'permits_expiring_soon' => (int) (clone $permitQuery)
                ->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)])
                ->whereIn('permit_status', MovementPermit::VALID_FOR_MOVEMENT_STATUSES)
                ->count(),
            'completed_movements' => (int) MovementHistory::query()
                ->where('status', MovementHistory::STATUS_COMPLETED)
                ->whereHas('permit', fn ($q) => $q->whereIn('farmer_id', $farmerIds))
                ->count(),
            'animals_moved_this_month' => (int) MovementHistory::query()
                ->where('movement_date', '>=', $monthStart)
                ->whereHas('permit', fn ($q) => $q->whereIn('farmer_id', $farmerIds))
                ->distinct('animal_id')
                ->count('animal_id'),
            'verification_searches_today' => (int) MovementLog::query()
                ->where('action_type', MovementLog::ACTION_VERIFIED)
                ->whereDate('action_date', $today)
                ->whereHas('movementPermit', fn ($q) => $q->whereIn('farmer_id', $farmerIds))
                ->count(),
            'total_permits' => (int) (clone $permitQuery)->count(),
            'expired_permits' => (int) (clone $permitQuery)->where('permit_status', MovementPermit::STATUS_EXPIRED)->count(),
        ];
    }

    /** @param  Collection<int, int>  $farmerIds */
    public function charts(Collection $farmerIds): array
    {
        return app(MovementPermitAnalyticsService::class)->charts($farmerIds);
    }
}
