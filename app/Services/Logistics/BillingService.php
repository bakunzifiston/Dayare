<?php

namespace App\Services\Logistics;

use App\Models\Client;
use App\Models\LogisticsInvoice;
use App\Models\LogisticsTrip;
use App\Models\User;
use App\Repositories\Logistics\InvoiceRepository;
use App\Services\Logistics\Concerns\ThrowsRuleViolation;
use Illuminate\Support\Carbon;

class BillingService
{
    use ThrowsRuleViolation;

    public function __construct(
        private CompanyService $companies,
        private InvoiceRepository $invoices
    ) {}

    public function generate(User $user, LogisticsTrip $trip, array $payload): LogisticsInvoice
    {
        $company = $this->companies->requireAccessible($user, (int) $trip->company_id);
        if (! in_array($trip->status, [LogisticsTrip::STATUS_COMPLETED, LogisticsTrip::STATUS_CANCELLED], true)) {
            $this->ruleViolation(__('Billing requires a completed or cancelled trip.'), 'trip_id');
        }

        $trip->loadMissing('order');
        if ($trip->order_id === null || $trip->order === null) {
            $this->ruleViolation(__('Trip must be linked to an order to bill.'), 'trip_id');
        }

        $client = Client::query()->find((int) $payload['client_id']);
        if ($client === null || (int) $client->business_id !== (int) $company->business_id) {
            $this->ruleViolation(__('Client must belong to your business.'), 'client_id');
        }

        $rawItems = collect((array) ($payload['items'] ?? []))
            ->filter(fn (array $row): bool => isset($row['description']) && trim((string) $row['description']) !== '')
            ->values()
            ->all();

        if ($rawItems === []) {
            $this->ruleViolation(__('Add at least one invoice line item.'), 'items');
        }

        $itemRows = [];
        $subtotal = 0.0;
        foreach ($rawItems as $row) {
            $qty = (float) ($row['quantity'] ?? 0);
            $unit = (float) ($row['unit_price'] ?? 0);
            $lineTotal = isset($row['total']) && $row['total'] !== '' && $row['total'] !== null
                ? (float) $row['total']
                : round($qty * $unit, 2);
            $subtotal += $lineTotal;
            $itemRows[] = [
                'description' => trim((string) $row['description']),
                'quantity' => $qty,
                'unit_price' => round($unit, 2),
                'total' => round($lineTotal, 2),
            ];
        }

        $tax = (float) ($payload['tax_amount'] ?? 0);
        $discount = (float) ($payload['discount_amount'] ?? 0);
        $subtotal = round($subtotal, 2);
        $tax = round(max(0, $tax), 2);
        $discount = round(max(0, $discount), 2);
        $total = round($subtotal + $tax - $discount, 2);

        $issuedAt = isset($payload['issued_at'])
            ? Carbon::parse($payload['issued_at'])
            : now();

        $dueDate = isset($payload['due_date']) && $payload['due_date'] !== null && $payload['due_date'] !== ''
            ? Carbon::parse($payload['due_date'])
            : null;

        return $this->invoices->createOrUpdateWithItems((int) $trip->id, [
            'order_id' => (int) $trip->order_id,
            'client_id' => (int) $client->id,
            'currency' => (string) $payload['currency'],
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'issued_at' => $issuedAt,
            'due_date' => $dueDate,
            'payment_status' => $payload['payment_status'] ?? LogisticsInvoice::PAYMENT_PENDING,
        ], $itemRows);
    }
}
