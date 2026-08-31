<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\FinanceInvoice;
use App\Models\FinancePayable;
use App\Services\Finance\FinanceEbmReconciler;
use App\Services\Processor\ProcessorDashboardCharts;
use App\Services\Processor\ProcessorDashboardContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function __invoke(Request $request, ProcessorDashboardCharts $charts): View|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }

        $activeBusinessId = $user->activeProcessorBusinessId();
        $role = $user->processorRoleForBusiness($activeBusinessId);

        $period = (string) $request->query('kpi_period', 'all');
        if (! in_array($period, ['all', 'day', 'month', 'year'], true)) {
            $period = 'all';
        }

        $range = $this->kpiDateRange($period);
        $filters = $this->chartFilters($period, $range);

        if ($activeBusinessId === null || $role === null) {
            return view('finance.dashboard', [
                'user' => $user,
                'role' => $role,
                'activeBusiness' => null,
                'kpiCards' => $this->emptyKpiCards(),
                'charts' => [],
                'kpiPeriod' => $period,
                'kpiPeriodLabel' => $range['label'],
                'recentInvoices' => [],
                'recentPayables' => [],
            ]);
        }

        $user->setActiveProcessorBusinessId($activeBusinessId);
        $business = Business::query()->find($activeBusinessId);
        $overview = $this->buildOverview($activeBusinessId, $filters);
        $ctx = ProcessorDashboardContext::forBusiness($activeBusinessId);
        $chartPayload = $charts->forRole(BusinessUser::ROLE_ACCOUNTANT, $ctx, $activeBusinessId, $filters);

        return view('finance.dashboard', [
            'user' => $user,
            'role' => $role,
            'activeBusiness' => $business,
            'kpiCards' => $overview['kpiCards'],
            'charts' => $chartPayload,
            'kpiPeriod' => $period,
            'kpiPeriodLabel' => $range['label'],
            'recentInvoices' => $this->recentInvoices($activeBusinessId),
            'recentPayables' => $this->recentPayables($activeBusinessId),
        ]);
    }

    /**
     * @return list<array{label: string, value: string, hint: ?string, icon: string, accent: bool, href: ?string, color: string, glyph: string}>
     */
    private function emptyKpiCards(): array
    {
        return [
            $this->kpiCard(__('Revenue'), '0', __('RWF'), 'currency-dollar', false, route('finance.invoices.index'), 'bucha-success', 'currency'),
            $this->kpiCard(__('AR outstanding'), '0', __('RWF'), 'receipt', false, route('finance.invoices.index'), 'slate', 'clock'),
            $this->kpiCard(__('AP outstanding'), '0', __('RWF'), 'clipboard-list', false, route('finance.payables.index'), 'slate', 'clock'),
            $this->kpiCard(__('Operating expenses'), '0', __('RWF'), 'clipboard-list', false, route('finance.expenses.index'), 'slate', 'clipboard'),
            $this->kpiCard(__('Collection rate'), '0%', null, 'chart-line', false, route('finance.invoices.index'), 'slate', 'check'),
            $this->kpiCard(__('EBM follow-up'), '0', null, 'receipt', false, route('finance.ebm.index'), 'slate', 'alert'),
        ];
    }

    /**
     * @param  array{is_filtered: bool, start: ?\Carbon\Carbon, end: ?\Carbon\Carbon}  $filters
     * @return array{kpiCards: list<array{label: string, value: string, hint: ?string, icon: string, accent: bool, href: ?string, color: string, glyph: string}>}
     */
    private function buildOverview(int $businessId, array $filters): array
    {
        $fmtMoney = fn (float $n): string => $this->formatAmount($n);
        $now = now();

        $revenueQuery = DB::table('finance_invoices')->where('business_id', $businessId);
        if ($filters['is_filtered']) {
            $this->applyDateWindow($revenueQuery, 'issued_at', $filters['start'], $filters['end']);
        }
        $revenue = (float) $revenueQuery->sum('total_amount');

        $collectedQuery = DB::table('finance_invoices')->where('business_id', $businessId);
        if ($filters['is_filtered']) {
            $this->applyDateWindow($collectedQuery, 'issued_at', $filters['start'], $filters['end']);
        }
        $collected = (float) $collectedQuery->sum('amount_paid');
        $collectionRate = $revenue > 0 ? (int) round(($collected / $revenue) * 100) : 0;

        $arOutstanding = (float) DB::table('finance_invoices')
            ->where('business_id', $businessId)
            ->sum(DB::raw('CASE WHEN total_amount > amount_paid THEN total_amount - amount_paid ELSE 0 END'));

        $apOutstanding = (float) DB::table('finance_payables')
            ->where('business_id', $businessId)
            ->sum(DB::raw('CASE WHEN total_amount > amount_paid THEN total_amount - amount_paid ELSE 0 END'));

        $arOverdue = (int) FinanceInvoice::query()
            ->where('business_id', $businessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now)
            ->whereRaw('amount_paid < total_amount')
            ->count();

        $apOverdue = (int) FinancePayable::query()
            ->where('business_id', $businessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now)
            ->whereRaw('amount_paid < total_amount')
            ->count();

        $expenseTotal = 0.0;
        if (Schema::hasTable('finance_expenses')) {
            $expenseQuery = DB::table('finance_expenses')->where('business_id', $businessId);
            if ($filters['is_filtered']) {
                $this->applyDateWindow($expenseQuery, 'expense_date', $filters['start'], $filters['end']);
            }
            $expenseTotal = (float) $expenseQuery->sum('amount');
        }

        $ebmFollowUp = 0;
        if (Schema::hasTable('finance_ebm_records')) {
            $ebmFollowUp = (int) app(FinanceEbmReconciler::class)->summary($businessId)['follow_up'];
        }

        return [
            'kpiCards' => [
                $this->kpiCard(
                    __('Revenue'),
                    $fmtMoney($revenue),
                    __('RWF'),
                    'currency-dollar',
                    false,
                    route('finance.invoices.index'),
                    'bucha-success',
                    'currency',
                ),
                $this->kpiCard(
                    __('AR outstanding'),
                    $fmtMoney($arOutstanding),
                    $arOverdue > 0 ? __(':count overdue', ['count' => $arOverdue]) : __('RWF'),
                    'receipt',
                    $arOverdue > 0,
                    route('finance.invoices.index'),
                    $arOverdue > 0 ? 'amber' : 'slate',
                    'clock',
                ),
                $this->kpiCard(
                    __('AP outstanding'),
                    $fmtMoney($apOutstanding),
                    $apOverdue > 0 ? __(':count overdue', ['count' => $apOverdue]) : __('RWF'),
                    'clipboard-list',
                    $apOverdue > 0,
                    route('finance.payables.index'),
                    $apOverdue > 0 ? 'amber' : 'slate',
                    'clock',
                ),
                $this->kpiCard(
                    __('Operating expenses'),
                    $fmtMoney($expenseTotal),
                    __('RWF'),
                    'clipboard-list',
                    false,
                    route('finance.expenses.index'),
                    'slate',
                    'clipboard',
                ),
                $this->kpiCard(
                    __('Collection rate'),
                    $collectionRate.'%',
                    $revenue > 0 ? __('Of billed') : null,
                    'chart-line',
                    $collectionRate < 90 && $revenue > 0,
                    route('finance.invoices.index'),
                    ($collectionRate < 90 && $revenue > 0) ? 'bucha' : 'bucha-success',
                    'check',
                ),
                $this->kpiCard(
                    __('EBM follow-up'),
                    (string) $ebmFollowUp,
                    $ebmFollowUp > 0 ? __('Need review') : __('All matched'),
                    'receipt',
                    $ebmFollowUp > 0,
                    route('finance.ebm.index'),
                    $ebmFollowUp > 0 ? 'bucha' : 'slate',
                    'alert',
                ),
            ],
        ];
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 0, '.', ',');
    }

    private function formatCompactMoney(float $amount): string
    {
        return $this->formatAmount($amount).' '.__('RWF');
    }

    /**
     * @return array{label: string, value: string, hint: ?string, icon: string, accent: bool, href: ?string, color: string, glyph: string}
     */
    private function kpiCard(
        string $label,
        string $value,
        ?string $hint,
        string $icon,
        bool $accent,
        ?string $href,
        string $color = 'slate',
        string $glyph = 'clipboard',
    ): array {
        return compact('label', 'value', 'hint', 'icon', 'accent', 'href', 'color', 'glyph');
    }

    /**
     * @return list<array{number: string, party: string, amount: string, state: string, state_label: string, date: string, href: string}>
     */
    private function recentInvoices(int $businessId): array
    {
        return FinanceInvoice::query()
            ->with('client')
            ->where('business_id', $businessId)
            ->whereNotIn('status', ['cancelled'])
            ->orderByRaw('CASE WHEN amount_paid < total_amount THEN 0 ELSE 1 END')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (FinanceInvoice $invoice): array {
                return [
                    'number' => (string) ($invoice->invoice_number ?: __('Invoice #:id', ['id' => $invoice->id])),
                    'party' => $invoice->client?->name ?? '—',
                    'amount' => $this->formatCompactMoney((float) $invoice->total_amount),
                    'state' => $invoice->paymentState(),
                    'state_label' => $invoice->paymentStateLabel(),
                    'date' => optional($invoice->issued_at ?? $invoice->created_at)->format('d M Y') ?? '—',
                    'href' => route('finance.invoices.show', $invoice),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{number: string, party: string, amount: string, state: string, state_label: string, date: string, href: string}>
     */
    private function recentPayables(int $businessId): array
    {
        return FinancePayable::query()
            ->with(['supplier', 'client', 'employee', 'casualWorker'])
            ->where('business_id', $businessId)
            ->whereNotIn('status', ['cancelled'])
            ->orderByRaw('CASE WHEN amount_paid < total_amount THEN 0 ELSE 1 END')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (FinancePayable $payable): array {
                return [
                    'number' => (string) ($payable->payable_number ?: __('Payable #:id', ['id' => $payable->id])),
                    'party' => $payable->counterpartyLabel(),
                    'amount' => $this->formatCompactMoney((float) $payable->total_amount),
                    'state' => $payable->paymentState(),
                    'state_label' => $payable->paymentStateLabel(),
                    'date' => optional($payable->issued_at ?? $payable->created_at)->format('d M Y') ?? '—',
                    'href' => route('finance.payables.show', $payable),
                ];
            })
            ->all();
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon, label: string}  $range
     * @return array{is_filtered: bool, start: ?\Carbon\Carbon, end: ?\Carbon\Carbon}
     */
    private function chartFilters(string $period, array $range): array
    {
        if ($period === 'all') {
            return [
                'is_filtered' => false,
                'start' => null,
                'end' => null,
            ];
        }

        return [
            'is_filtered' => true,
            'start' => $range['start'],
            'end' => $range['end'],
        ];
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
