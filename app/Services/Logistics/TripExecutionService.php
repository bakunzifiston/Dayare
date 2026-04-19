<?php

namespace App\Services\Logistics;

use App\Events\Logistics\TripCompleted;
use App\Events\Logistics\TripStarted;
use App\Models\LogisticsComplianceDocument;
use App\Models\LogisticsDriver;
use App\Models\LogisticsOrder;
use App\Models\LogisticsTrip;
use App\Models\LogisticsVehicle;
use App\Models\User;
use App\Repositories\Logistics\ComplianceRepository;
use App\Repositories\Logistics\TripRepository;
use App\Services\Logistics\Concerns\ThrowsRuleViolation;
use Illuminate\Support\Facades\DB;

class TripExecutionService
{
    use ThrowsRuleViolation;

    public function __construct(
        private CompanyService $companies,
        private TripRepository $trips,
        private ComplianceRepository $compliance
    ) {}

    public function start(User $user, LogisticsTrip $trip, ?string $actualDeparture = null): LogisticsTrip
    {
        $this->companies->requireAccessible($user, (int) $trip->company_id);
        if (! in_array($trip->status, [LogisticsTrip::STATUS_SCHEDULED, LogisticsTrip::STATUS_LOADED], true)) {
            $this->ruleViolation(__('Only scheduled or loaded trips can start.'), 'status');
        }

        $trip->loadMissing('order');
        $needsExportDocs = $trip->order !== null && $trip->order->service_type === LogisticsOrder::SERVICE_TYPE_EXPORT;
        if ($needsExportDocs) {
            $validTypes = $this->compliance->validByTrip((int) $trip->id)->pluck('type')->all();
            foreach ([LogisticsComplianceDocument::TYPE_HEALTH_CERTIFICATE, LogisticsComplianceDocument::TYPE_MOVEMENT_PERMIT] as $required) {
                if (! in_array($required, $validTypes, true)) {
                    $this->ruleViolation(__('Cannot start export trip without valid health certificate and movement permit.'), 'compliance_documents');
                }
            }
        }

        $trip->actual_departure = $actualDeparture ?? now()->toDateTimeString();
        $trip->status = LogisticsTrip::STATUS_IN_TRANSIT;
        $this->trips->save($trip);
        event(new TripStarted((int) $trip->id));

        return $trip->refresh()->load(['vehicle', 'driver', 'order', 'originLocation', 'destinationLocation', 'complianceDocuments']);
    }

    public function complete(User $user, LogisticsTrip $trip, array $payload): LogisticsTrip
    {
        $this->companies->requireAccessible($user, (int) $trip->company_id);
        if (! in_array($trip->status, [
            LogisticsTrip::STATUS_IN_TRANSIT,
            LogisticsTrip::STATUS_AT_CHECKPOINT,
            LogisticsTrip::STATUS_DELAYED,
        ], true)) {
            $this->ruleViolation(__('This trip cannot be completed in its current state.'), 'status');
        }

        $completed = DB::transaction(function () use ($trip, $payload) {
            $alloc = (int) $trip->allocated_weight_kg;
            $status = (string) $payload['status'];

            if ($status === LogisticsTrip::STATUS_COMPLETED) {
                $delivered = max(0, (int) ($payload['delivered_weight_kg'] ?? 0));
                $loss = max(0, (int) ($payload['loss_weight_kg'] ?? 0));
                if (($delivered + $loss) > $alloc) {
                    $this->ruleViolation(__('Delivered + loss exceeds allocated weight.'), 'delivered_weight_kg');
                }
                $trip->delivered_weight_kg = $delivered;
                $trip->loss_weight_kg = $loss;
            } elseif ($status === LogisticsTrip::STATUS_CANCELLED) {
                $trip->delivered_weight_kg = 0;
                $trip->loss_weight_kg = $alloc;
            }

            $trip->actual_arrival = $payload['actual_arrival'] ?? now()->toDateTimeString();
            $trip->status = $status;
            $this->trips->save($trip);

            if ($trip->vehicle !== null) {
                $trip->vehicle->status = LogisticsVehicle::STATUS_AVAILABLE;
                $trip->vehicle->save();
            }
            if ($trip->driver !== null) {
                $trip->driver->status = LogisticsDriver::STATUS_AVAILABLE;
                $trip->driver->save();
            }

            return $trip->refresh()->load(['vehicle', 'driver', 'order', 'originLocation', 'destinationLocation', 'complianceDocuments']);
        });

        event(new TripCompleted((int) $completed->id));

        return $completed;
    }
}
