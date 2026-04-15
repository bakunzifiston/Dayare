<?php

namespace App\Services\Logistics;

use App\Events\Logistics\TripPlanned;
use App\Models\LogisticsComplianceDocument;
use App\Models\LogisticsDriver;
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

        $orderIds = collect((array) $payload['orders'])->pluck('order_id')->map(fn ($id) => (int) $id)->all();
        $approvedOrders = $this->orders->approvedByCompanyAndIds((int) $company->id, $orderIds)->keyBy('id');
        if (count($orderIds) !== $approvedOrders->count()) {
            $this->ruleViolation(__('All selected orders must be approved.'), 'orders');
        }

        $totalUnits = 0;
        $totalWeight = 0.0;
        $hasLivestock = false;
        $syncPayload = [];
        foreach ((array) $payload['orders'] as $row) {
            $orderId = (int) $row['order_id'];
            $allocated = (int) $row['allocated_quantity'];
            $order = $approvedOrders->get($orderId);
            if ($order === null || $allocated <= 0 || $allocated > (int) $order->quantity) {
                $this->ruleViolation(__('Allocated quantity invalid.'), 'orders');
            }

            $alreadyReserved = $this->trips->reservedQuantityForOrder($orderId);
            $alreadyDelivered = $this->trips->deliveredQuantityForOrder($orderId);
            $availableToAllocate = max(0, ((int) $order->quantity) - $alreadyReserved - $alreadyDelivered);
            if ($allocated > $availableToAllocate) {
                $this->ruleViolation(__('Allocated quantity exceeds remaining order balance.'), 'orders');
            }

            $totalUnits += $allocated;
            if ($order->weight !== null && (int) $order->quantity > 0) {
                $totalWeight += (((float) $order->weight) / ((int) $order->quantity)) * $allocated;
            }
            if ($order->species !== null && trim((string) $order->species) !== '') {
                $hasLivestock = true;
            }
            $syncPayload[$orderId] = ['allocated_quantity' => $allocated, 'delivered_quantity' => 0, 'loss_quantity' => 0];
        }
        if ($totalUnits > (int) $vehicle->max_units) {
            $this->ruleViolation(__('Vehicle max units exceeded.'), 'orders');
        }
        if ($vehicle->max_weight !== null && $totalWeight > (float) $vehicle->max_weight) {
            $this->ruleViolation(__('Vehicle max weight exceeded.'), 'orders');
        }
        if ($hasLivestock) {
            $docTypes = collect((array) ($payload['compliance_documents'] ?? []))->pluck('type')->all();
            foreach ([LogisticsComplianceDocument::TYPE_HEALTH_CERTIFICATE, LogisticsComplianceDocument::TYPE_MOVEMENT_PERMIT] as $required) {
                if (! in_array($required, $docTypes, true)) {
                    $this->ruleViolation(__('Livestock trip requires health certificate and movement permit.'), 'compliance_documents');
                }
            }
        }

        return DB::transaction(function () use ($payload, $company, $vehicle, $driver, $syncPayload) {
            $trip = $this->trips->create([
                'company_id' => (int) $company->id,
                'vehicle_id' => (int) $vehicle->id,
                'driver_id' => (int) $driver->id,
                'planned_departure' => $payload['planned_departure'],
                'planned_arrival' => $payload['planned_arrival'],
                'status' => LogisticsTrip::STATUS_SCHEDULED,
            ]);
            $this->trips->syncOrders($trip, $syncPayload);
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

            return $trip->refresh()->load(['vehicle', 'driver', 'orders', 'tripOrders', 'complianceDocuments']);
        });
    }
}

