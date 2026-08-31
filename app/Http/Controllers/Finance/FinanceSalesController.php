<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\ResolvesProcessorFinanceContext;
use App\Models\DeliveryConfirmation;
use App\Models\FinanceInvoice;
use App\Models\FinancePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceSalesController extends Controller
{
    use ResolvesProcessorFinanceContext;

    public function index(Request $request)
    {
        $businessId = $this->activeBusinessId($request);
        $query = FinanceInvoice::query()
            ->with(['client', 'facility', 'lines', 'animalIntake.client', 'ebmRecord', 'financePayments'])
            ->where('business_id', $businessId)
            ->whereNotIn('status', ['cancelled']);

        if ($request->filled('from')) {
            $query->whereDate('issued_at', '>=', (string) $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('issued_at', '<=', (string) $request->query('to'));
        }
        if ($request->filled('payment_state')) {
            $state = (string) $request->query('payment_state');
            if ($state === FinancePayment::STATE_PAID) {
                $query->whereColumn('amount_paid', '>=', 'total_amount')->where('total_amount', '>', 0);
            } elseif ($state === FinancePayment::STATE_PENDING) {
                $query->where('amount_paid', '>', 0)->whereColumn('amount_paid', '<', 'total_amount');
            } elseif ($state === FinancePayment::STATE_UNPAID) {
                $query->where(function ($w): void {
                    $w->where('amount_paid', '<=', 0)->orWhereNull('amount_paid');
                });
            }
        }
        if ($request->filled('facility_id')) {
            $query->where('facility_id', (int) $request->query('facility_id'));
        }
        if ($request->filled('q')) {
            $q = '%'.trim((string) $request->query('q')).'%';
            $query->where(function ($w) use ($q): void {
                $w->where('invoice_number', 'like', $q)
                    ->orWhere('notes', 'like', $q)
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $q));
            });
        }

        $count = (clone $query)->count();
        $total = (float) (clone $query)->sum('total_amount');
        $outstanding = (float) (clone $query)->toBase()->sum(DB::raw(
            'CASE WHEN amount_paid < total_amount THEN total_amount - amount_paid ELSE 0 END'
        ));

        $ebmFollowUp = (clone $query)
            ->whereNotIn('status', ['draft'])
            ->whereDoesntHave('ebmRecord')
            ->count();

        $sales = $query->orderByDesc('issued_at')->orderByDesc('id')->paginate(20)->withQueryString();

        $unbilledDeliveries = DeliveryConfirmation::query()
            ->with(['client', 'transportTrip.originFacility'])
            ->whereHas('transportTrip.originFacility', fn ($q) => $q->where('business_id', $businessId))
            ->whereDoesntHave('financeInvoice')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return view('finance.sales.index', [
            'sales' => $sales,
            'unbilledDeliveries' => $unbilledDeliveries,
            'facilities' => $this->businessFacilities($businessId),
            'summary' => [
                'count' => $count,
                'total' => $total,
                'outstanding' => $outstanding,
                'ebm_follow_up' => $ebmFollowUp,
            ],
            'filters' => [
                'from' => (string) $request->query('from', ''),
                'to' => (string) $request->query('to', ''),
                'payment_state' => (string) $request->query('payment_state', ''),
                'facility_id' => (string) $request->query('facility_id', ''),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }
}
