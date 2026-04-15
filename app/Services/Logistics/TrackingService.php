<?php

namespace App\Services\Logistics;

use App\Models\LogisticsTrackingLog;
use App\Models\LogisticsTrip;
use App\Models\User;
use App\Repositories\Logistics\TrackingRepository;
use App\Services\Logistics\Concerns\ThrowsRuleViolation;

class TrackingService
{
    use ThrowsRuleViolation;

    public function __construct(
        private CompanyService $companies,
        private TrackingRepository $tracking
    ) {}

    public function log(User $user, LogisticsTrip $trip, array $payload): LogisticsTrackingLog
    {
        $this->companies->requireAccessible($user, (int) $trip->company_id);
        if (! in_array($trip->status, [
            LogisticsTrip::STATUS_LOADING,
            LogisticsTrip::STATUS_IN_TRANSIT,
            LogisticsTrip::STATUS_DELIVERED,
            LogisticsTrip::STATUS_FAILED,
        ], true)) {
            $this->ruleViolation(__('Tracking requires trip to be active or completed.'), 'trip_id');
        }

        if (in_array($trip->status, [LogisticsTrip::STATUS_DELIVERED, LogisticsTrip::STATUS_FAILED], true)
            && ! in_array($payload['status'], [LogisticsTrackingLog::STATUS_DELIVERED, LogisticsTrackingLog::STATUS_FAILED], true)) {
            $this->ruleViolation(__('Completed trips can only be logged as delivered or failed.'), 'status');
        }

        return $this->tracking->create([
            'trip_id' => (int) $trip->id,
            'timestamp' => $payload['timestamp'],
            'location' => $payload['location'],
            'status' => $payload['status'],
            'notes' => $payload['notes'] ?? null,
        ]);
    }
}

