<?php

namespace App\Services\Logistics;

use App\Events\Logistics\TripPlanned;
use App\Models\LogisticsComplianceDocument;
use App\Models\LogisticsDriver;
use App\Models\LogisticsOrder;
use App\Models\LogisticsTrip;
use App\Models\LogisticsVehicle;
use App\Models\User;
use App\Repositories\Logistics\ComplianceRepository;
use App\Repositories\Logistics\DriverRepository;
use App\Repositories\Logistics\OrderRepository;
use App\Repositories\Logistics\TripRepository;
use App\Repositories\Logistics\VehicleRepository;
use App\Services\Logistics\Concerns\ThrowsRuleViolation;
use Illuminate\Support\Facades\DB;

class TripPlanningService
{
    use ThrowsRuleViolation;

    public function __construct(
        private CompanyService $companies,
        private VehicleRepository $vehicles,
        private DriverRepository $drivers,
        private OrderRepository $orders,
        private TripRepository $trips,
        private ComplianceRepository $compliance
    ) {}

    public function plan(User $user, array $payload): LogisticsTrip
    {
        $company = $this->companies->requireAccessible($user, (int) $payload['company_id']);
        if (! $company->hasValidLicense()) {
            $this->ruleViolation(__('Company license expired. Cannot create trip.'), 'company_id');
        }

        $vehicle = $this->vehicles->find((int) $payload['vehicle_id']);
        if ($vehicle === null || (int) $vehicle->company_id !== (int) $company->id) {
            $this->ruleViolation(__('Vehicle does not belong to this company.'), 'vehicle_id');
        }
        if ($vehicle->status !== LogisticsVehicle::STATUS_AVAILABLE) {
            $this->ruleViolation(__('Vehicle is not available.'), 'vehicle_id');
        }
        if ($this->trips->hasActiveVehicleTrip((int) $vehicle->id)) {
            $this->ruleViolation(__('Vehicle already assigned to an active trip.'), 'vehicle_id');
        }

        $driver = $this->drivers->find((int) $payload['driver_id']);
        if ($driver === null || (int) $driver->company_id !== (int) $company->id) {
            $this->ruleViolation(__('Driver does not belong to this company.'), 'driver_id');
        }
        if ($driver->status !== LogisticsDriver::STATUS_AVAILABLE || ! $driver->hasValidLicense()) {
            $this->ruleViolation(__('Driver unavailable or license expired.'), 'driver_id');
        }
        if ($this->trips->hasActiveDriverTrip((int) $driver->id)) {
            $this->ruleViolation(__('Driver already assigned to active trip.'), 'driver_id');
        }

        $order = $this->orders->find((int) $payload['order_id']);
        if ($order === null || (int) $order->company_id !== (int) $company->id) {
            $this->ruleViolation(__('Order not found for this company.'), 'order_id');
        }
        if ($order->status !== LogisticsOrder::STATUS_CONFIRMED) {
            $this->ruleViolation(__('Only confirmed orders can be scheduled.'), 'order_id');
        }

        $allocated = (int) $payload['allocated_weight_kg'];
        $capKg = $order->allocatableWeightKg();
        if ($allocated <= 0 || $allocated > $capKg) {
            $this->ruleViolation(__('Allocated weight (kg) is invalid for this order.'), 'allocated_weight_kg');
        }

        $alreadyReserved = $this->trips->reservedQuantityForOrder((int) $order->id);
        $alreadyDelivered = $this->trips->deliveredQuantityForOrder((int) $order->id);
        $availableToAllocate = max(0, $capKg - $alreadyReserved - $alreadyDelivered);
        if ($allocated > $availableToAllocate) {
            $this->ruleViolation(__('Allocated weight exceeds remaining order capacity.'), 'allocated_weight_kg');
        }

        $totalUnits = $allocated;
        $totalWeight = (float) $allocated;
        if ($totalUnits > (int) $vehicle->max_units) {
            $this->ruleViolation(__('Vehicle max units exceeded.'), 'allocated_weight_kg');
        }
        if ($vehicle->max_weight !== null && $totalWeight > (float) $vehicle->max_weight) {
            $this->ruleViolation(__('Vehicle max weight exceeded.'), 'allocated_weight_kg');
        }

        $needsExportDocs = $order->service_type === LogisticsOrder::SERVICE_TYPE_EXPORT;
        if ($needsExportDocs) {
            $docTypes = collect((array) ($payload['compliance_documents'] ?? []))->pluck('type')->all();
            foreach ([LogisticsComplianceDocument::TYPE_HEALTH_CERTIFICATE, LogisticsComplianceDocument::TYPE_MOVEMENT_PERMIT] as $required) {
                if (! in_array($required, $docTypes, true)) {
                    $this->ruleViolation(__('Export trips require a health certificate and movement permit.'), 'compliance_documents');
                }
            }
        }

        return DB::transaction(function () use ($payload, $company, $vehicle, $driver, $order, $allocated) {
            $trip = $this->trips->create([
                'company_id' => (int) $company->id,
                'order_id' => (int) $order->id,
                'origin_location_id' => (int) $payload['origin_location_id'],
                'destination_location_id' => (int) $payload['destination_location_id'],
                'vehicle_id' => (int) $vehicle->id,
                'driver_id' => (int) $driver->id,
                'planned_departure' => $payload['planned_departure'],
                'planned_arrival' => $payload['planned_arrival'],
                'status' => LogisticsTrip::STATUS_SCHEDULED,
                'notes' => $payload['notes'] ?? null,
                'allocated_weight_kg' => $allocated,
                'delivered_weight_kg' => 0,
                'loss_weight_kg' => 0,
            ]);
            foreach ((array) ($payload['compliance_documents'] ?? []) as $doc) {
                $this->compliance->create([
                    'trip_id' => (int) $trip->id,
                    'type' => $doc['type'],
                    'reference_id' => $doc['reference_id'] ?? null,
                    'status' => $doc['status'],
                ]);
            }
            $vehicle->status = LogisticsVehicle::STATUS_IN_USE;
            $vehicle->save();
            $driver->status = LogisticsDriver::STATUS_ASSIGNED;
            $driver->save();
            event(new TripPlanned((int) $trip->id));

            return $trip->refresh()->load(['vehicle', 'driver', 'order', 'originLocation', 'destinationLocation', 'complianceDocuments']);
        });
    }
}
