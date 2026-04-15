<?php

namespace App\Services\Logistics;

use App\Models\LogisticsInvoice;
use App\Models\LogisticsTrip;
use App\Models\User;
use App\Repositories\Logistics\InvoiceRepository;
use App\Services\Logistics\Concerns\ThrowsRuleViolation;

class BillingService
{
    use ThrowsRuleViolation;

    public function __construct(
        private CompanyService $companies,
        private InvoiceRepository $invoices
    ) {}

    public function generate(User $user, LogisticsTrip $trip, array $payload): LogisticsInvoice
    {
        $this->companies->requireAccessible($user, (int) $trip->company_id);
        if (! in_array($trip->status, [LogisticsTrip::STATUS_DELIVERED, LogisticsTrip::STATUS_FAILED], true)) {
            $this->ruleViolation(__('Billing requires delivered or failed trip.'), 'trip_id');
        }

        $billableUnits = (int) $trip->tripOrders()->sum('delivered_quantity');
        if ($billableUnits === 0) {
            $billableUnits = (int) $trip->tripOrders()->sum('allocated_quantity');
        }

        $base = (float) $payload['base_cost'];
        $costPerKm = (float) $payload['cost_per_km'];
        $distanceKm = (float) ($payload['distance_km'] ?? 0);
        $costPerUnit = (float) $payload['cost_per_unit'];
        $extra = (float) ($payload['extra_charges'] ?? 0);
        $total = $base + ($costPerKm * $distanceKm) + ($costPerUnit * $billableUnits) + $extra;

        return $this->invoices->createOrUpdateByTrip((int) $trip->id, [
            'base_cost' => round($base, 2),
            'cost_per_km' => round($costPerKm, 2),
            'distance_km' => round($distanceKm, 2),
            'cost_per_unit' => round($costPerUnit, 2),
            'extra_charges' => round($extra, 2),
            'total_amount' => round($total, 2),
            'payment_status' => $payload['payment_status'] ?? LogisticsInvoice::PAYMENT_PENDING,
        ]);
    }
}

