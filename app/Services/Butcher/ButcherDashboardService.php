<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherHygieneLog;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOrder;
use App\Models\ButcherSale;
use App\Models\ButcherSaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ButcherDashboardService
{
    private const TIMEZONE = 'Africa/Kigali';

    public function __construct(
        private readonly ButcherFinanceService $finance,
        private readonly ButcherComplianceService $compliance,
        private readonly ButcherStorageService $storage,
        private readonly ButcherCuttingService $cutting,
    ) {}

    /**
     * @param  array{period?: string, from_input?: string, to_input?: string, range_label?: string, all_time?: bool}|null  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, ?Carbon $rangeFrom = null, ?Carbon $rangeTo = null, ?array $filters = null): array
    {
        $businesses = Business::query()
            ->whereIn('id', $user->accessibleButcherBusinessIds())
            ->orderBy('business_name')
            ->get();

        $business = $businesses->first();
        $today = $this->today()->startOfDay();
        $allTime = (bool) ($filters['all_time'] ?? ($rangeFrom === null && $rangeTo === null && (($filters['period'] ?? 'all') === 'all')));

        if (! $allTime) {
            $rangeFrom = ($rangeFrom ?? $today->copy())->copy()->startOfDay();
            $rangeTo = ($rangeTo ?? $today->copy())->copy()->startOfDay();
            if ($rangeFrom->gt($rangeTo)) {
                [$rangeFrom, $rangeTo] = [$rangeTo->copy(), $rangeFrom->copy()];
            }
        } else {
            $rangeFrom = null;
            $rangeTo = null;
        }

        $filters = array_merge([
            'period' => $allTime ? 'all' : ($rangeFrom?->equalTo($rangeTo) ? 'today' : 'custom'),
            'from_input' => $rangeFrom?->toDateString() ?? '',
            'to_input' => $rangeTo?->toDateString() ?? '',
            'range_label' => $allTime
                ? __('All time')
                : ($rangeFrom->equalTo($rangeTo)
                    ? $rangeFrom->isoFormat('D MMM YYYY')
                    : $rangeFrom->isoFormat('D MMM YYYY').' – '.$rangeTo->isoFormat('D MMM YYYY')),
            'all_time' => $allTime,
        ], $filters ?? []);

        if ($business === null) {
            return [
                'businesses' => $businesses,
                'business' => null,
                'greeting' => $this->greeting(),
                'today_date' => $this->today()->isoFormat('dddd, D MMMM YYYY'),
                'today' => null,
                'overview' => null,
                'filters' => $filters,
                'charts' => [
                    'today' => [],
                    'overview' => [],
                ],
            ];
        }

        if ($allTime) {
            $periodSales = $this->completedSalesQuery($business, null, null);
            $periodMetrics = $this->salesMetrics($periodSales);
            $priorMetrics = [
                'sales_count' => 0,
                'revenue' => 0.0,
                'kg_sold' => 0.0,
                'avg_sale_value' => 0.0,
            ];
            $compareLabel = '';
            $financeFrom = Carbon::parse('2000-01-01', self::TIMEZONE)->startOfDay();
            $financeTo = $today->copy()->endOfDay();
        } else {
            $dayCount = $rangeFrom->diffInDays($rangeTo) + 1;
            $prevTo = $rangeFrom->copy()->subDay();
            $prevFrom = $prevTo->copy()->subDays($dayCount - 1);
            $compareLabel = $dayCount === 1 ? __('vs yesterday') : __('vs prior period');

            $periodSales = $this->completedSalesQuery($business, $rangeFrom, $rangeTo);
            $priorSales = $this->completedSalesQuery($business, $prevFrom, $prevTo);
            $periodMetrics = $this->salesMetrics($periodSales);
            $priorMetrics = $this->salesMetrics($priorSales);
            $financeFrom = $rangeFrom->copy()->startOfDay();
            $financeTo = $rangeTo->copy()->endOfDay();
        }

        $periodPl = $this->finance->getProfitAndLoss($business, $financeFrom, $financeTo);
        $cashflow = $this->finance->getCashFlow($business, $financeFrom, $financeTo);
        $complianceAlerts = $this->compliance->getComplianceAlerts($business);
        $storage = $this->storage->getStorageSummary($business);
        $yield = $this->yieldForRange($business, $rangeFrom, $rangeTo);
        $waste = $this->wasteForRange($business, $rangeFrom, $rangeTo);

        $openOrders = (int) $business->butcherOrders()
            ->whereIn('status', [
                ButcherOrder::STATUS_PENDING,
                ButcherOrder::STATUS_CONFIRMED,
                ButcherOrder::STATUS_READY,
            ])
            ->count();

        $creditOutstanding = (float) $business->butcherCustomers()->sum('outstanding_balance');

        $receivingQuery = $business->butcherDeliveries();
        if ($rangeFrom !== null) {
            $receivingQuery->whereDate('received_at', '>=', $rangeFrom->toDateString());
        }
        if ($rangeTo !== null) {
            $receivingQuery->whereDate('received_at', '<=', $rangeTo->toDateString());
        }
        $receivingKg = (float) (clone $receivingQuery)->sum('received_weight_kg');
        $receivingCount = (int) (clone $receivingQuery)->count();

        $breachedBatches = (int) $business->butcherInventoryBatches()
            ->where('temperature_breach', true)
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->count();

        $hygieneToday = $this->hygieneTodayLabel($business, $complianceAlerts);
        $staffHealthLabel = $this->staffHealthLabel($complianceAlerts);
        $auditReadiness = $this->auditReadinessPct($business);

        $todayAtGlance = [
            'sales_count' => $this->metricWithTrend(
                $periodMetrics['sales_count'],
                $priorMetrics['sales_count'],
                fn (int $v) => (string) $v,
                $compareLabel,
            ),
            'revenue' => $this->metricWithTrend(
                $periodMetrics['revenue'],
                $priorMetrics['revenue'],
                fn (float $v) => 'RWF '.number_format($v, 0),
                $compareLabel,
            ),
            'kg_sold' => $this->metricWithTrend(
                $periodMetrics['kg_sold'],
                $priorMetrics['kg_sold'],
                fn (float $v) => number_format($v, 1).' kg',
                $compareLabel,
            ),
            'avg_sale_value' => $this->metricWithTrend(
                $periodMetrics['avg_sale_value'],
                $priorMetrics['avg_sale_value'],
                fn (float $v) => 'RWF '.number_format($v, 0),
                $compareLabel,
            ),
        ];

        $financeBlock = [
            'revenue_mtd' => [
                'value' => 'RWF '.number_format((float) $periodPl['revenue'], 0),
                'subtext' => $filters['range_label'],
                'raw' => (float) $periodPl['revenue'],
            ],
            'cogs' => [
                'value' => 'RWF '.number_format((float) $periodPl['cogs'], 0),
                'subtext' => $filters['range_label'],
                'raw' => (float) $periodPl['cogs'],
            ],
            'gross_margin_pct' => [
                'value' => number_format((float) $periodPl['gross_margin_pct'], 1).'%',
                'color' => $periodPl['gross_margin_pct'] >= 20 ? 'success' : ($periodPl['gross_margin_pct'] >= 10 ? 'warning' : 'danger'),
                'subtext' => __('Gross margin'),
                'raw' => (float) $periodPl['gross_margin_pct'],
            ],
            'credit_outstanding' => [
                'value' => 'RWF '.number_format($creditOutstanding, 0),
                'color' => $creditOutstanding > 0 ? 'warning' : 'success',
                'subtext' => __('Customer balances'),
                'raw' => $creditOutstanding,
            ],
            'cash_in' => [
                'value' => 'RWF '.number_format((float) ($cashflow['total_cash_in'] ?? 0), 0),
                'subtext' => __('Cash in'),
                'raw' => (float) ($cashflow['total_cash_in'] ?? 0),
            ],
            'cash_out' => [
                'value' => 'RWF '.number_format((float) ($cashflow['total_cash_out'] ?? 0), 0),
                'subtext' => __('Cash out'),
                'raw' => (float) ($cashflow['total_cash_out'] ?? 0),
            ],
        ];

        $salesBlock = [
            'open_orders' => [
                'value' => (string) $openOrders,
                'subtext' => __('Customer orders open'),
                'raw' => $openOrders,
            ],
        ];

        $complianceKpis = [
            'hygiene_log' => $hygieneToday,
            'staff_health' => $staffHealthLabel,
            'permits_expiring' => [
                'value' => (string) $complianceAlerts['expiring_permit_count'],
                'color' => $complianceAlerts['expiring_permit_count'] > 0 ? 'warning' : 'success',
                'subtext' => __('Within 60 days'),
                'raw' => (int) $complianceAlerts['expiring_permit_count'],
            ],
            'audit_readiness' => [
                'value' => number_format($auditReadiness, 0).'%',
                'color' => $auditReadiness >= 80 ? 'success' : ($auditReadiness >= 50 ? 'warning' : 'danger'),
                'subtext' => __('Hygiene pass rate (30d)'),
                'raw' => $auditReadiness,
            ],
        ];

        $todaySection = [
            'sales_count' => $todayAtGlance['sales_count'],
            'revenue' => $todayAtGlance['revenue'],
            'kg_sold' => $todayAtGlance['kg_sold'],
            'avg_sale_value' => $todayAtGlance['avg_sale_value'],
            'receiving' => [
                'value' => number_format($receivingKg, 1).' kg',
                'subtext' => __(':count delivery(ies)', ['count' => $receivingCount]),
                'raw_kg' => $receivingKg,
                'raw_count' => $receivingCount,
            ],
            'open_orders' => $salesBlock['open_orders'],
            'expiring_soon' => [
                'value' => (string) $storage['expiring_soon'],
                'color' => $storage['expiring_soon'] > 0 ? 'warning' : 'success',
                'subtext' => __('Best-before within 1 day'),
                'raw' => (int) $storage['expiring_soon'],
            ],
            'temperature_breaches' => [
                'value' => (string) $breachedBatches,
                'color' => $breachedBatches > 0 ? 'danger' : 'success',
                'subtext' => __('Active breached batches'),
                'raw' => $breachedBatches,
            ],
            'temp_breaches_today' => [
                'value' => (string) $storage['temp_breaches_today'],
                'subtext' => __('Breach logs today'),
                'raw' => (int) $storage['temp_breaches_today'],
            ],
            'hygiene_log' => $hygieneToday,
            'credit_outstanding' => $financeBlock['credit_outstanding'],
        ];

        $overviewSection = [
            'finance' => [
                'revenue_mtd' => $financeBlock['revenue_mtd'],
                'cogs' => $financeBlock['cogs'],
                'gross_margin_pct' => $financeBlock['gross_margin_pct'],
                'cash_in' => $financeBlock['cash_in'],
                'cash_out' => $financeBlock['cash_out'],
            ],
            'operations' => [
                'yield_kg' => [
                    'value' => number_format((float) $yield['total_yield_kg'], 1).' kg',
                    'subtext' => __('Cut yield'),
                    'raw' => (float) $yield['total_yield_kg'],
                ],
                'waste_kg' => [
                    'value' => number_format((float) $waste['waste_kg'], 1).' kg',
                    'subtext' => __('Waste'),
                    'raw' => (float) $waste['waste_kg'],
                ],
                'avg_wastage_pct' => [
                    'value' => number_format((float) $yield['avg_wastage_pct'], 1).'%',
                    'subtext' => __('Avg cutting wastage'),
                    'raw' => (float) $yield['avg_wastage_pct'],
                ],
            ],
            'compliance' => [
                'staff_health' => $staffHealthLabel,
                'permits_expiring' => $complianceKpis['permits_expiring'],
                'audit_readiness' => $complianceKpis['audit_readiness'],
            ],
        ];

        return [
            'businesses' => $businesses,
            'business' => $business,
            'greeting' => $this->greeting(),
            'today_date' => $this->today()->isoFormat('dddd, D MMMM YYYY'),
            'filters' => $filters,
            // Relocated primary payload (Phase 10)
            'today' => $todaySection,
            'overview' => $overviewSection,
            'charts' => $this->buildCharts($business, $rangeFrom, $rangeTo, $filters['range_label']),
            // Backward-compatible aliases for regression / any legacy reads
            'today_at_glance' => $todayAtGlance,
            'finance' => $financeBlock,
            'sales' => $salesBlock,
            'compliance_kpis' => $complianceKpis,
            'alerts' => $this->buildAlerts($business, $complianceAlerts, $creditOutstanding, $breachedBatches),
            'recent_sales' => $this->recentSales($business, $rangeFrom, $rangeTo),
        ];
    }

    private function greeting(): string
    {
        $hour = $this->today()->hour;

        return match (true) {
            $hour < 12 => __('Good morning'),
            $hour < 17 => __('Good afternoon'),
            default => __('Good evening'),
        };
    }

    private function today(): Carbon
    {
        return now(self::TIMEZONE);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<ButcherSale>
     */
    private function completedSalesQuery(Business $business, ?Carbon $from, ?Carbon $to)
    {
        $query = $business->butcherSales()
            ->where('status', ButcherSale::STATUS_COMPLETED);

        if ($from !== null) {
            $query->whereDate('sale_date', '>=', $from->toDateString());
        }
        if ($to !== null) {
            $query->whereDate('sale_date', '<=', $to->toDateString());
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ButcherSale>  $query
     * @return array{sales_count: int, revenue: float, kg_sold: float, avg_sale_value: float}
     */
    private function salesMetrics($query): array
    {
        $salesCount = (int) (clone $query)->count();
        $revenue = (float) (clone $query)->sum('total_amount');

        $saleIds = (clone $query)->pluck('id');
        $kgSold = $saleIds->isEmpty()
            ? 0.0
            : (float) ButcherSaleItem::query()->whereIn('sale_id', $saleIds)->sum('quantity_kg');

        return [
            'sales_count' => $salesCount,
            'revenue' => $revenue,
            'kg_sold' => $kgSold,
            'avg_sale_value' => $salesCount > 0 ? round($revenue / $salesCount, 2) : 0.0,
        ];
    }

    /**
     * @return array{value: string, trend: ?string, trend_text: ?string, color: ?string, subtext: ?string}
     */
    private function metricWithTrend(float|int $current, float|int $previous, callable $formatter, string $compareLabel = ''): array
    {
        $diff = $current - $previous;
        $trend = null;
        $trendText = null;

        if ($diff !== 0.0 && $compareLabel !== '') {
            $trend = $diff > 0 ? 'up' : 'down';
            $trendText = ($diff > 0 ? '↑ ' : '↓ ').$this->formatTrendDelta($diff, $current, $previous).' '.$compareLabel;
        }

        return [
            'value' => $formatter($current),
            'trend' => $trend,
            'trend_text' => $trendText,
            'color' => null,
            'subtext' => null,
            'raw' => $current,
        ];
    }

    private function formatTrendDelta(float|int $diff, float|int $current, float|int $previous): string
    {
        if (is_float($current) || is_float($previous) || is_float($diff)) {
            return number_format(abs((float) $diff), 1);
        }

        return (string) abs((int) $diff);
    }

    /**
     * @param  array<string, mixed>  $alerts
     * @return array{value: string, color: ?string, subtext: string}
     */
    private function hygieneTodayLabel(Business $business, array $alerts): array
    {
        $missing = (int) $alerts['missing_hygiene_count'];
        $outletCount = (int) $business->butcherOutlets()->where('status', 'active')->count();

        if ($outletCount === 0) {
            return [
                'value' => __('N/A'),
                'subtext' => __('No outlets configured'),
                'raw' => 0,
            ];
        }

        if ($missing === 0) {
            return [
                'value' => __('Complete'),
                'color' => 'success',
                'subtext' => __('All outlets logged today'),
                'raw' => 0,
            ];
        }

        return [
            'value' => (string) $missing,
            'color' => 'warning',
            'subtext' => __('Outlet(s) missing today'),
            'raw' => $missing,
        ];
    }

    /**
     * @param  array<string, mixed>  $alerts
     * @return array{value: string, color: ?string, subtext: string}
     */
    private function staffHealthLabel(array $alerts): array
    {
        $count = (int) $alerts['expiring_health_count'];

        if ($count === 0) {
            return [
                'value' => __('Valid'),
                'color' => 'success',
                'subtext' => __('No cards expiring soon'),
                'raw' => 0,
            ];
        }

        return [
            'value' => (string) $count,
            'color' => 'warning',
            'subtext' => __('Expiring or expired'),
            'raw' => $count,
        ];
    }

    private function auditReadinessPct(Business $business): float
    {
        $from = $this->today()->copy()->subDays(30)->toDateString();
        $to = $this->today()->toDateString();

        $logs = $business->butcherHygieneLogs()
            ->whereDate('log_date', '>=', $from)
            ->whereDate('log_date', '<=', $to)
            ->get();

        if ($logs->isEmpty()) {
            return 0.0;
        }

        $passed = $logs->where('status', ButcherHygieneLog::STATUS_PASS)->count();

        return round(($passed / $logs->count()) * 100, 1);
    }

    /**
     * @param  array<string, mixed>  $complianceAlerts
     * @return list<array{level: string, message: string}>
     */
    private function buildAlerts(
        Business $business,
        array $complianceAlerts,
        float $creditOutstanding,
        int $breachedBatches,
    ): array {
        $alerts = [];

        if ($breachedBatches > 0) {
            $alerts[] = [
                'level' => 'danger',
                'message' => __(':count active batch(es) flagged for temperature breach', ['count' => $breachedBatches]),
            ];
        }

        foreach ($complianceAlerts['missing_hygiene_today'] as $outlet) {
            $alerts[] = [
                'level' => 'warning',
                'message' => __('Hygiene log missing today — :outlet', ['outlet' => $outlet->name]),
            ];
        }

        foreach ($complianceAlerts['expiring_permits'] as $permit) {
            $alerts[] = [
                'level' => 'warning',
                'message' => __('Permit :number expires :date', [
                    'number' => $permit->permit_number,
                    'date' => $permit->expiry_date?->toDateString() ?? '—',
                ]),
            ];
        }

        foreach ($complianceAlerts['expiring_health_cards'] as $health) {
            $alerts[] = [
                'level' => 'warning',
                'message' => __('Staff health card expiring — :name', ['name' => $health->user?->name ?? __('Staff')]),
            ];
        }

        foreach ($complianceAlerts['overdue_sanitation'] as $record) {
            $alerts[] = [
                'level' => 'danger',
                'message' => __('Overdue sanitation — :equipment (:outlet)', [
                    'equipment' => $record->equipment_name,
                    'outlet' => $record->outlet?->name ?? '—',
                ]),
            ];
        }

        if ($creditOutstanding > 100000) {
            $alerts[] = [
                'level' => 'info',
                'message' => __('Credit outstanding: RWF :amount', ['amount' => number_format($creditOutstanding, 0)]),
            ];
        }

        return array_slice($alerts, 0, 8);
    }

    /**
     * @return Collection<int, array{number: string, customer: string, item: string, amount: float, payment: string, time: string}>
     */
    private function recentSales(Business $business, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = $business->butcherSales()
            ->with(['customer', 'items.product'])
            ->where('status', ButcherSale::STATUS_COMPLETED);

        if ($from !== null) {
            $query->whereDate('sale_date', '>=', $from->toDateString());
        }
        if ($to !== null) {
            $query->whereDate('sale_date', '<=', $to->toDateString());
        }

        return $query
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function (ButcherSale $sale) {
                $items = $sale->items->map(fn (ButcherSaleItem $item) => $item->product?->name ?? __('Item'))->filter()->unique();
                $itemLabel = $items->isNotEmpty()
                    ? $items->take(2)->implode(', ').($items->count() > 2 ? '…' : '')
                    : '—';

                return [
                    'number' => $sale->sale_number,
                    'customer' => $sale->customer?->name ?? __('Walk-in'),
                    'item' => $itemLabel,
                    'amount' => (float) $sale->total_amount,
                    'payment' => $sale->payment_method,
                    'time' => $sale->created_at
                        ? $sale->created_at->timezone(self::TIMEZONE)->format('H:i')
                        : '—',
                ];
            });
    }

    /**
     * @return array{total_yield_kg: float, total_wastage_kg: float, avg_wastage_pct: float}
     */
    private function yieldForRange(Business $business, ?Carbon $from, ?Carbon $to): array
    {
        $query = $business->butcherCuttingSessions()
            ->where('status', \App\Models\ButcherCuttingSession::STATUS_CLOSED);

        if ($from !== null) {
            $query->whereDate('session_date', '>=', $from->toDateString());
        }
        if ($to !== null) {
            $query->whereDate('session_date', '<=', $to->toDateString());
        }

        $sessions = $query->get();

        return [
            'total_yield_kg' => (float) $sessions->sum('total_cuts_weight_kg'),
            'total_wastage_kg' => (float) $sessions->sum('wastage_kg'),
            'avg_wastage_pct' => $sessions->isNotEmpty()
                ? round((float) $sessions->avg('wastage_pct'), 2)
                : 0.0,
        ];
    }

    /**
     * @return array{waste_kg: float}
     */
    private function wasteForRange(Business $business, ?Carbon $from, ?Carbon $to): array
    {
        $query = $business->butcherDisposalLogs();
        if ($from !== null) {
            $query->whereDate('disposed_at', '>=', $from->toDateString());
        }
        if ($to !== null) {
            $query->whereDate('disposed_at', '<=', $to->toDateString());
        }

        return ['waste_kg' => (float) $query->sum('weight_disposed_kg')];
    }

    /**
     * Chart specs for Chart.js via x-workspace.chart-grid + processor-dashboard-charts.
     *
     * @return array{today: list<array<string, mixed>>, overview: list<array<string, mixed>>}
     */
    private function buildCharts(
        Business $business,
        ?Carbon $from,
        ?Carbon $to,
        string $rangeLabel,
    ): array {
        $series = config('bucha.chart.series', ['#A11D1E', '#7A1516', '#3C3C3B', '#718096', '#D69E2E', '#38A169']);

        return [
            'today' => [
                $this->revenueTrendChart($business, $from, $to, $series, $rangeLabel),
                $this->stockByMeatChart($business, $series),
            ],
            'overview' => [],
        ];
    }

    /**
     * @param  list<string>  $series
     * @return array<string, mixed>
     */
    private function revenueTrendChart(Business $business, ?Carbon $from, ?Carbon $to, array $series, string $rangeLabel): array
    {
        $today = $this->today()->startOfDay();
        $chartTo = ($to ?? $today)->copy()->startOfDay();
        // Keep the line chart readable: at most 30 days, ending at range end (or today for all-time).
        $chartFrom = ($from ?? $chartTo->copy()->subDays(29))->copy()->startOfDay();
        if ($from === null) {
            $chartFrom = $chartTo->copy()->subDays(29);
            $rangeLabel = __('Last 30 days');
        }

        $days = min(62, max(1, $chartFrom->diffInDays($chartTo) + 1));
        if ($days > 30 && $from === null) {
            $days = 30;
            $chartFrom = $chartTo->copy()->subDays(29);
        }

        $rows = $business->butcherSales()
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->whereDate('sale_date', '>=', $chartFrom->toDateString())
            ->whereDate('sale_date', '<=', $chartTo->toDateString())
            ->selectRaw('sale_date, SUM(total_amount) as revenue')
            ->groupBy('sale_date')
            ->pluck('revenue', 'sale_date');

        $labels = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $chartFrom->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->isoFormat('MMM D');
            $data[] = round((float) ($rows[$key] ?? 0), 0);
        }

        $color = $series[0] ?? '#A11D1E';

        return [
            'id' => 'chart-butcher-revenue-trend',
            'title' => __('Revenue trend'),
            'subtitle' => $rangeLabel,
            'height' => 220,
            'ariaLabel' => __('Daily revenue'),
            'type' => 'line',
            'yCallback' => 'compact',
            'labels' => $labels,
            'datasets' => [[
                'label' => __('Revenue (RWF)'),
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => $color,
            ]],
            'legend' => [
                ['color' => $color, 'label' => __('Revenue (RWF)')],
            ],
        ];
    }

    /**
     * @param  list<string>  $series
     * @return array<string, mixed>
     */
    private function stockByMeatChart(Business $business, array $series): array
    {
        $rows = $business->butcherInventoryBatches()
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->where('remaining_weight_kg', '>', 0)
            ->selectRaw('meat_type, SUM(remaining_weight_kg) as kg')
            ->groupBy('meat_type')
            ->pluck('kg', 'meat_type');

        $labels = [];
        $data = [];
        $colors = [];
        $i = 0;
        foreach ($rows as $meat => $kg) {
            $labels[] = __(ucfirst((string) $meat));
            $data[] = round((float) $kg, 1);
            $colors[] = $series[$i % count($series)];
            $i++;
        }

        return $this->pieSpec(
            'butcher-stock-meat',
            __('Stock by meat type'),
            __('Active inventory (kg)'),
            __('Remaining stock by meat type'),
            $labels,
            $data,
            $colors,
            'pie',
        );
    }

    /**
     * @param  list<string>  $labels
     * @param  list<float|int>  $data
     * @param  list<string>  $colors
     * @return array<string, mixed>
     */
    private function pieSpec(
        string $slug,
        string $title,
        string $subtitle,
        string $ariaLabel,
        array $labels,
        array $data,
        array $colors,
        string $type = 'pie',
    ): array {
        return [
            'id' => 'chart-'.$slug,
            'title' => $title,
            'subtitle' => $subtitle,
            'height' => 220,
            'ariaLabel' => $ariaLabel,
            'type' => $type,
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
            'legend' => collect($labels)->map(fn (string $label, int $i) => [
                'color' => $colors[$i] ?? '#A11D1E',
                'label' => $label,
            ])->all(),
            'emptyMessage' => __('No data for this period.'),
        ];
    }
}
