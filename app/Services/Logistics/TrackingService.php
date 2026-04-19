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

        $activeForTracking = [
            LogisticsTrip::STATUS_LOADED,
            LogisticsTrip::STATUS_IN_TRANSIT,
            LogisticsTrip::STATUS_AT_CHECKPOINT,
            LogisticsTrip::STATUS_DELAYED,
        ];
        $terminal = [
            LogisticsTrip::STATUS_COMPLETED,
            LogisticsTrip::STATUS_CANCELLED,
        ];

        if (! in_array($trip->status, array_merge($activeForTracking, $terminal), true)) {
            $this->ruleViolation(__('Tracking requires an active or finished trip.'), 'trip_id');
        }

        if (in_array($trip->status, $terminal, true)
            && $payload['status'] !== LogisticsTrackingLog::STATUS_COMPLETED) {
            $this->ruleViolation(__('Completed or cancelled trips can only receive a completed tracking event.'), 'status');
        }

        return $this->tracking->create([
            'trip_id' => (int) $trip->id,
            'location_id' => $payload['location_id'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'status' => $payload['status'],
            'event_time' => $payload['event_time'],
            'notes' => $payload['notes'] ?? null,
        ]);
    }
}
