<?php

namespace App\Services\Logistics;

use App\Events\Logistics\TripCompleted;
use App\Events\Logistics\TripStarted;
use App\Models\LogisticsComplianceDocument;
use App\Models\LogisticsDriver;
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
        if (! in_array($trip->status, [LogisticsTrip::STATUS_SCHEDULED, LogisticsTrip::STATUS_LOADING], true)) {
            $this->ruleViolation(__('Only scheduled/loading trips can start.'), 'status');
        }

        $hasLivestock = $trip->orders()->whereNotNull('species')->where('species', '!=', '')->exists();
        if ($hasLivestock) {
            $validTypes = $this->compliance->validByTrip((int) $trip->id)->pluck('type')->all();
            foreach ([LogisticsComplianceDocument::TYPE_HEALTH_CERTIFICATE, LogisticsComplianceDocument::TYPE_MOVEMENT_PERMIT] as $required) {
                if (! in_array($required, $validTypes, true)) {
                    $this->ruleViolation(__('Cannot start livestock trip without valid health certificate and movement permit.'), 'compliance_documents');
                }
            }
        }

        $trip->actual_departure = $actualDeparture ?? now()->toDateTimeString();
        $trip->status = LogisticsTrip::STATUS_IN_TRANSIT;
        $this->trips->save($trip);
        event(new TripStarted((int) $trip->id));

        return $trip->refresh()->load(['vehicle', 'driver', 'orders', 'tripOrders', 'complianceDocuments']);
    }

    public function complete(User $user, LogisticsTrip $trip, array $payload): LogisticsTrip
    {
        $this->companies->requireAccessible($user, (int) $trip->company_id);
        if ($trip->status !== LogisticsTrip::STATUS_IN_TRANSIT) {
            $this->ruleViolation(__('Only in-transit trips can be completed.'), 'status');
        }

        $completed = DB::transaction(function () use ($trip, $payload) {
            $rows = $trip->tripOrders()->get()->keyBy('order_id');
            $deliveryRows = collect((array) ($payload['deliveries'] ?? []))
                ->keyBy(fn (array $delivery): int => (int) ($delivery['order_id'] ?? 0));

            foreach ($rows as $orderId => $tripOrder) {
                /** @var array|null $delivery */
                $delivery = $deliveryRows->get((int) $orderId);

                if ($delivery === null && $payload['status'] === LogisticsTrip::STATUS_DELIVERED) {
                    $this->ruleViolation(__('Every trip order must include delivered/loss quantities.'), 'deliveries');
                }

                if ($delivery !== null) {
                    $delivered = max(0, (int) $delivery['delivered_quantity']);
                    $loss = max(0, (int) ($delivery['loss_quantity'] ?? 0));
                    if (($delivered + $loss) > (int) $tripOrder->allocated_quantity) {
                        $this->ruleViolation(__('Delivered + loss exceeds allocation.'), 'deliveries');
                    }
                    $tripOrder->delivered_quantity = $delivered;
                    $tripOrder->loss_quantity = $loss;
                    $tripOrder->save();
                } elseif ($payload['status'] === LogisticsTrip::STATUS_FAILED) {
                    // When a trip fails without explicit delivery rows, mark the full allocation as loss.
                    $tripOrder->delivered_quantity = 0;
                    $tripOrder->loss_quantity = (int) $tripOrder->allocated_quantity;
                    $tripOrder->save();
                }
            }

            $trip->actual_arrival = $payload['actual_arrival'] ?? now()->toDateTimeString();
            $trip->status = $payload['status'];
            $this->trips->save($trip);

            if ($trip->vehicle !== null) {
                $trip->vehicle->status = LogisticsVehicle::STATUS_AVAILABLE;
                $trip->vehicle->save();
            }
            if ($trip->driver !== null) {
                $trip->driver->status = LogisticsDriver::STATUS_AVAILABLE;
                $trip->driver->save();
            }

            return $trip->refresh()->load(['vehicle', 'driver', 'orders', 'tripOrders', 'complianceDocuments']);
        });

        event(new TripCompleted((int) $completed->id));

        return $completed;
    }
}

