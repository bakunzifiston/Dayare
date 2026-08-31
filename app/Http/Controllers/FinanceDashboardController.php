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
                'kpiCards' => $this->emptyKpiCards($range['label']),
                'charts' => [],
                'kpiPeriod' => $period,
                'kpiPeriodLabel' => $range['label'],
                'quickLinks' => $this->quickLinks(),
            ]);
        }

        $user->setActiveProcessorBusinessId($activeBusinessId);
        $business = Business::query()->find($activeBusinessId);
        $kpiCards = $this->buildKpiCards($activeBusinessId, $filters, $range['label']);
        $ctx = ProcessorDashboardContext::forBusiness($activeBusinessId);
        $chartPayload = $charts->forRole(BusinessUser::ROLE_ACCOUNTANT, $ctx, $activeBusinessId, $filters);

        return view('finance.dashboard', [
            'user' => $user,
            'role' => $role,
            'activeBusiness' => $business,
            'kpiCards' => $kpiCards,
            'charts' => $chartPayload,
            'kpiPeriod' => $period,
            'kpiPeriodLabel' => $range['label'],
            'quickLinks' => $this->quickLinks(),
        ]);
    }

    /**
     * @return list<array{label: string, value: string, hint: ?string, icon: string, accent: bool, href: ?string}>
     */
    private function emptyKpiCards(string $periodLabel): array
    {
        return [
            $this->kpiCard(__('Revenue'), '0 '.__('RWF'), $periodLabel, 'currency-dollar', false, route('finance.invoices.index')),
            $this->kpiCard(__('AR outstanding'), '0 '.__('RWF'), __('0 overdue'), 'receipt', false, route('finance.invoices.index')),
            $this->kpiCard(__('AP outstanding'), '0 '.__('RWF'), __('0 overdue'), 'clipboard-list', false, route('finance.payables.index')),
            $this->kpiCard(__('Operating expenses'), '0 '.__('RWF'), $periodLabel, 'clipboard-list', false, route('finance.expenses.index')),
            $this->kpiCard(__('Collection rate'), '0%', $periodLabel, 'chart-line', false, route('finance.invoices.index')),
            $this->kpiCard(__('EBM follow-up'), '0', __('Missing or mismatched'), 'receipt', false, route('finance.ebm.index')),
        ];
    }

    /**
     * @param  array{is_filtered: bool, start: ?\Carbon\Carbon, end: ?\Carbon\Carbon}  $filters
     * @return list<array{label: string, value: string, hint: ?string, icon: string, accent: bool, href: ?string}>
     */
    private function buildKpiCards(int $businessId, array $filters, string $periodLabel): array
    {
        $fmtMoney = fn (float $n): string => $this->formatCompactMoney($n);
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
            ->sum(DB::raw('GREATEST(total_amount - amount_paid, 0)'));

        $apOutstanding = (float) DB::table('finance_payables')
            ->where('business_id', $businessId)
            ->sum(DB::raw('GREATEST(total_amount - amount_paid, 0)'));

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
            $this->kpiCard(
                __('Revenue'),
                $fmtMoney($revenue),
                $periodLabel,
                'currency-dollar',
                false,
                route('finance.invoices.index'),
            ),
            $this->kpiCard(
                __('AR outstanding'),
                $fmtMoney($arOutstanding),
                __(':count overdue', ['count' => $arOverdue]),
                'receipt',
                $arOverdue > 0,
                route('finance.invoices.index'),
            ),
            $this->kpiCard(
                __('AP outstanding'),
                $fmtMoney($apOutstanding),
                __(':count overdue', ['count' => $apOverdue]),
                'clipboard-list',
                $apOverdue > 0,
                route('finance.payables.index'),
            ),
            $this->kpiCard(
                __('Operating expenses'),
                $fmtMoney($expenseTotal),
                $periodLabel,
                'clipboard-list',
                false,
                route('finance.expenses.index'),
            ),
            $this->kpiCard(
                __('Collection rate'),
                $collectionRate.'%',
                $periodLabel,
                'chart-line',
                $collectionRate < 90 && $revenue > 0,
                route('finance.invoices.index'),
            ),
            $this->kpiCard(
                __('EBM follow-up'),
                (string) $ebmFollowUp,
                __('Missing or mismatched'),
                'receipt',
                $ebmFollowUp > 0,
                route('finance.ebm.index'),
            ),
        ];
    }

    private function formatCompactMoney(float $amount): string
    {
        $abs = abs($amount);

        if ($abs >= 1_000_000_000) {
            $value = $amount / 1_000_000_000;
            $formatted = number_format($value, $value >= 100 ? 0 : 1).'B';
        } elseif ($abs >= 1_000_000) {
            $value = $amount / 1_000_000;
            $formatted = number_format($value, $value >= 100 ? 0 : 1).'M';
        } elseif ($abs >= 1_000) {
            $value = $amount / 1_000;
            $formatted = number_format($value, $value >= 100 ? 0 : 1).'K';
        } else {
            $formatted = number_format($amount, 0, '.', ',');
        }

        return $formatted.' '.__('RWF');
    }

    /**
     * @return array{label: string, value: string, hint: ?string, icon: string, accent: bool, href: ?string}
     */
    private function kpiCard(
        string $label,
        string $value,
        ?string $hint,
        string $icon,
        bool $accent,
        ?string $href,
    ): array {
        return compact('label', 'value', 'hint', 'icon', 'accent', 'href');
    }

    /**
     * @return list<array{label: string, route: string}>
     */
    private function quickLinks(): array
    {
        return [
            ['label' => __('Daily sales'), 'route' => 'finance.sales.index'],
            ['label' => __('AR invoices'), 'route' => 'finance.invoices.index'],
            ['label' => __('EBM invoices'), 'route' => 'finance.ebm.index'],
            ['label' => __('AP payables'), 'route' => 'finance.payables.index'],
            ['label' => __('Expenses'), 'route' => 'finance.expenses.index'],
            ['label' => __('Cost allocations'), 'route' => 'finance.cost-allocations.index'],
            ['label' => __('Casual workers'), 'route' => 'finance.casual-workers.index'],
        ];
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
