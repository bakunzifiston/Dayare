<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function __invoke(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }

        $activeBusinessId = $user->activeProcessorBusinessId();
        $role = $user->processorRoleForBusiness($activeBusinessId);

        if ($role === BusinessUser::ROLE_ACCOUNTANT
            && $activeBusinessId !== null
            && ! $user->ownsBusiness($activeBusinessId)) {
            return redirect()->route('dashboard');
        }

        if ($activeBusinessId === null || $role === null) {
            return view('finance.dashboard', [
                'user' => $user,
                'role' => $role,
                'activeBusiness' => null,
                'kpis' => [
                    'revenue' => 0.0,
                    'ap_outstanding' => 0.0,
                    'gross_margin_proxy_pct' => 0.0,
                    'overdue_receivables_count' => 0,
                    'overdue_payables_count' => 0,
                ],
                'kpiPeriod' => 'all',
                'kpiPeriodLabel' => (string) __('All time'),
            ]);
        }

        $user->setActiveProcessorBusinessId($activeBusinessId);
        $business = Business::query()->find($activeBusinessId);
        $period = (string) $request->query('kpi_period', 'all');
        if (! in_array($period, ['all', 'day', 'month', 'year'], true)) {
            $period = 'all';
        }

        $range = $this->kpiDateRange($period);
        $now = now();

        $invoiceQuery = DB::table('finance_invoices')->where('business_id', $activeBusinessId);
        $payableQuery = DB::table('finance_payables')->where('business_id', $activeBusinessId);
        $allocationQuery = DB::table('finance_cost_allocations')->where('business_id', $activeBusinessId);

        if ($period !== 'all') {
            $this->applyDateWindow($invoiceQuery, 'issued_at', $range['start'], $range['end']);
            $this->applyDateWindow($payableQuery, 'issued_at', $range['start'], $range['end']);
            $allocationQuery->whereBetween('allocation_date', [$range['start']->toDateString(), $range['end']->toDateString()]);
        }

        $revenue = (float) $invoiceQuery->sum('total_amount');
        $apOutstanding = (float) $payableQuery->sum(DB::raw('GREATEST(total_amount - amount_paid, 0)'));
        $allocatedCosts = (float) $allocationQuery->sum('amount');
        $grossMarginProxyPct = $revenue > 0
            ? round((($revenue - ($apOutstanding + $allocatedCosts)) / $revenue) * 100, 1)
            : 0.0;

        $overdueReceivablesCount = DB::table('finance_invoices')
            ->where('business_id', $activeBusinessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now)
            ->whereRaw('amount_paid < total_amount')
            ->count();

        $overduePayablesCount = DB::table('finance_payables')
            ->where('business_id', $activeBusinessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now)
            ->whereRaw('amount_paid < total_amount')
            ->count();

        return view('finance.dashboard', [
            'user' => $user,
            'role' => $role,
            'activeBusiness' => $business,
            'kpis' => [
                'revenue' => $revenue,
                'ap_outstanding' => $apOutstanding,
                'gross_margin_proxy_pct' => $grossMarginProxyPct,
                'overdue_receivables_count' => $overdueReceivablesCount,
                'overdue_payables_count' => $overduePayablesCount,
            ],
            'kpiPeriod' => $period,
            'kpiPeriodLabel' => $range['label'],
        ]);
    }

    private function applyDateWindow(Builder $query, string $column, \Carbon\Carbon $start, \Carbon\Carbon $end): void
    {
        $query->where(function (Builder $q) use ($column, $start, $end): void {
            $q->whereBetween($column, [$start, $end])
                ->orWhere(function (Builder $fallback) use ($column, $start, $end): void {
                    $fallback->whereNull($column)->whereBetween('created_at', [$start, $end]);
                });
        });
    }

    /**
     * @return array{start: \Carbon\Carbon, end: \Carbon\Carbon, label: string}
     */
    private function kpiDateRange(string $period): array
    {
        $now = now();

        return match ($period) {
            'day' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => $now->format('M j, Y'),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->format('F Y'),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => (string) $now->year,
            ],
            default => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => (string) __('All time'),
            ],
        };
    }
}
