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
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $businesses = Business::query()
            ->whereIn('id', $user->accessibleButcherBusinessIds())
            ->orderBy('business_name')
            ->get();

        $business = $businesses->first();

        if ($business === null) {
            return [
                'businesses' => $businesses,
                'business' => null,
                'greeting' => $this->greeting(),
                'today_date' => $this->today()->isoFormat('dddd, D MMMM YYYY'),
                'today' => null,
                'overview' => null,
            ];
        }

        $today = $this->today();
        $yesterday = $today->copy()->subDay();
        $monthStart = $today->copy()->startOfMonth();

        $todaySales = $this->completedSalesQuery($business, $today, $today);
        $yesterdaySales = $this->completedSalesQuery($business, $yesterday, $yesterday);

        $todayMetrics = $this->salesMetrics($todaySales);
        $yesterdayMetrics = $this->salesMetrics($yesterdaySales);

        $monthPl = $this->finance->getProfitAndLoss($business, $monthStart, $today->copy()->endOfDay());
        $cashflow = $this->finance->getCashFlow($business, $monthStart, $today->copy()->endOfDay());
        $complianceAlerts = $this->compliance->getComplianceAlerts($business);
        $storage = $this->storage->getStorageSummary($business);
        $yield = $this->cutting->getYieldReport($business, '30d');
        $waste = $this->storage->getWasteSummary($business, '30d');

        $openOrders = (int) $business->butcherOrders()
            ->whereIn('status', [
                ButcherOrder::STATUS_PENDING,
                ButcherOrder::STATUS_CONFIRMED,
                ButcherOrder::STATUS_READY,
            ])
            ->count();

        $creditOutstanding = (float) $business->butcherCustomers()->sum('outstanding_balance');

        $receivingTodayKg = (float) $business->butcherDeliveries()
            ->whereDate('received_at', $today->toDateString())
            ->sum('received_weight_kg');
        $receivingTodayCount = (int) $business->butcherDeliveries()
            ->whereDate('received_at', $today->toDateString())
            ->count();

        $breachedBatches = (int) $business->butcherInventoryBatches()
            ->where('temperature_breach', true)
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->count();

        $hygieneToday = $this->hygieneTodayLabel($business, $complianceAlerts);
        $staffHealthLabel = $this->staffHealthLabel($complianceAlerts);
        $auditReadiness = $this->auditReadinessPct($business);

        $todayAtGlance = [
            'sales_count' => $this->metricWithTrend(
                $todayMetrics['sales_count'],
                $yesterdayMetrics['sales_count'],
                fn (int $v) => (string) $v,
            ),
            'revenue' => $this->metricWithTrend(
                $todayMetrics['revenue'],
                $yesterdayMetrics['revenue'],
                fn (float $v) => 'RWF '.number_format($v, 0),
            ),
            'kg_sold' => $this->metricWithTrend(
                $todayMetrics['kg_sold'],
                $yesterdayMetrics['kg_sold'],
                fn (float $v) => number_format($v, 1).' kg',
            ),
            'avg_sale_value' => $this->metricWithTrend(
                $todayMetrics['avg_sale_value'],
                $yesterdayMetrics['avg_sale_value'],
                fn (float $v) => 'RWF '.number_format($v, 0),
            ),
        ];

        $financeBlock = [
            'revenue_mtd' => [
                'value' => 'RWF '.number_format((float) $monthPl['revenue'], 0),
                'subtext' => $monthStart->isoFormat('MMM D').' – '.$today->isoFormat('MMM D'),
                'raw' => (float) $monthPl['revenue'],
            ],
            'cogs' => [
                'value' => 'RWF '.number_format((float) $monthPl['cogs'], 0),
                'subtext' => __('Month to date'),
                'raw' => (float) $monthPl['cogs'],
            ],
            'gross_margin_pct' => [
                'value' => number_format((float) $monthPl['gross_margin_pct'], 1).'%',
                'color' => $monthPl['gross_margin_pct'] >= 20 ? 'success' : ($monthPl['gross_margin_pct'] >= 10 ? 'warning' : 'danger'),
                'subtext' => __('Gross margin'),
                'raw' => (float) $monthPl['gross_margin_pct'],
            ],
            'credit_outstanding' => [
                'value' => 'RWF '.number_format($creditOutstanding, 0),
                'color' => $creditOutstanding > 0 ? 'warning' : 'success',
                'subtext' => __('Customer balances'),
                'raw' => $creditOutstanding,
            ],
            'cash_in' => [
                'value' => 'RWF '.number_format((float) ($cashflow['total_cash_in'] ?? 0), 0),
                'subtext' => __('Cash in (MTD)'),
                'raw' => (float) ($cashflow['total_cash_in'] ?? 0),
            ],
            'cash_out' => [
                'value' => 'RWF '.number_format((float) ($cashflow['total_cash_out'] ?? 0), 0),
                'subtext' => __('Cash out (MTD)'),
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
                'value' => number_format($receivingTodayKg, 1).' kg',
                'subtext' => __(':count delivery(ies)', ['count' => $receivingTodayCount]),
                'raw_kg' => $receivingTodayKg,
                'raw_count' => $receivingTodayCount,
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
                    'subtext' => __('Cut yield (30d)'),
                    'raw' => (float) $yield['total_yield_kg'],
                ],
                'waste_kg' => [
                    'value' => number_format((float) $waste['waste_kg'], 1).' kg',
                    'subtext' => __('Waste (30d)'),
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
            'today_date' => $today->isoFormat('dddd, D MMMM YYYY'),
            // Relocated primary payload (Phase 10)
            'today' => $todaySection,
            'overview' => $overviewSection,
            // Backward-compatible aliases for regression / any legacy reads
            'today_at_glance' => $todayAtGlance,
            'finance' => $financeBlock,
            'sales' => $salesBlock,
            'compliance_kpis' => $complianceKpis,
            'alerts' => $this->buildAlerts($business, $complianceAlerts, $creditOutstanding, $breachedBatches),
            'recent_sales' => $this->recentSales($business),
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
    private function completedSalesQuery(Business $business, Carbon $from, Carbon $to)
    {
        return $business->butcherSales()
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString());
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
    private function metricWithTrend(float|int $current, float|int $previous, callable $formatter): array
    {
        $diff = $current - $previous;
        $trend = null;
        $trendText = null;

        if ($diff !== 0.0) {
            $trend = $diff > 0 ? 'up' : 'down';
            $trendText = ($diff > 0 ? '↑ ' : '↓ ').$this->formatTrendDelta($diff, $current, $previous).' '.__('vs yesterday');
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
    private function recentSales(Business $business): Collection
    {
        return $business->butcherSales()
            ->with(['customer', 'items.product'])
            ->where('status', ButcherSale::STATUS_COMPLETED)
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
}
