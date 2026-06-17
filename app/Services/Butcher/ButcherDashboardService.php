<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherCuttingSession;
use App\Models\ButcherCustomer;
use App\Models\ButcherHygieneLog;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOrder;
use App\Models\ButcherPurchaseOrder;
use App\Models\ButcherSale;
use App\Models\ButcherSaleItem;
use App\Models\ButcherTemperatureLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ButcherDashboardService
{
    private const TIMEZONE = 'Africa/Kigali';

    /** @var list<string> */
    private const STOCK_MEAT_TYPES = [
        ButcherInventoryBatch::MEAT_BEEF,
        ButcherInventoryBatch::MEAT_GOAT,
        ButcherInventoryBatch::MEAT_PORK,
        ButcherInventoryBatch::MEAT_POULTRY,
    ];

    public function __construct(
        private readonly ButcherOnboardingService $onboarding,
        private readonly ButcherFinanceService $finance,
        private readonly ButcherStorageService $storage,
        private readonly ButcherComplianceService $compliance,
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
            ];
        }

        $today = $this->today();
        $yesterday = $today->copy()->subDay();
        $monthStart = $today->copy()->startOfMonth();

        $todaySales = $this->completedSalesQuery($business, $today, $today);
        $yesterdaySales = $this->completedSalesQuery($business, $yesterday, $yesterday);

        $todayMetrics = $this->salesMetrics($todaySales);
        $yesterdayMetrics = $this->salesMetrics($yesterdaySales);

        $storageSummary = $this->storage->getStorageSummary($business);
        $monthPl = $this->finance->getProfitAndLoss($business, $monthStart, $today->copy()->endOfDay());
        $complianceAlerts = $this->compliance->getComplianceAlerts($business);

        $openSessions = (int) $business->butcherCuttingSessions()
            ->where('status', ButcherCuttingSession::STATUS_OPEN)
            ->count();

        $avgYield = $this->averageYieldPct($business);

        $pendingDeliveries = (int) $business->butcherPurchaseOrders()
            ->whereIn('status', [
                ButcherPurchaseOrder::STATUS_SENT,
                ButcherPurchaseOrder::STATUS_CONFIRMED,
            ])
            ->count();

        $openOrders = (int) $business->butcherOrders()
            ->whereIn('status', [
                ButcherOrder::STATUS_PENDING,
                ButcherOrder::STATUS_CONFIRMED,
                ButcherOrder::STATUS_READY,
            ])
            ->count();

        $creditOutstanding = (float) $business->butcherCustomers()->sum('outstanding_balance');

        $hygieneToday = $this->hygieneTodayLabel($business, $complianceAlerts);
        $staffHealthLabel = $this->staffHealthLabel($complianceAlerts);
        $auditReadiness = $this->auditReadinessPct($business);

        return [
            'businesses' => $businesses,
            'business' => $business,
            'greeting' => $this->greeting(),
            'today_date' => $today->isoFormat('dddd, D MMMM YYYY'),
            'today_at_glance' => [
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
            ],
            'stock' => [
                'batches_in_storage' => [
                    'value' => (string) $storageSummary['batches_in_storage'],
                    'subtext' => __('Active inventory batches'),
                ],
                'total_stock_kg' => [
                    'value' => number_format((float) $storageSummary['kg_in_storage'], 1).' kg',
                    'subtext' => __('Remaining in cold storage'),
                ],
                'expiring_soon' => [
                    'value' => (string) $storageSummary['expiring_soon'],
                    'color' => $storageSummary['expiring_soon'] > 0 ? 'warning' : 'success',
                    'subtext' => __('Best before within 24h'),
                ],
                'temp_status' => $this->tempStatusKpi($storageSummary['temp_breaches_today']),
            ],
            'finance' => [
                'revenue_mtd' => [
                    'value' => 'RWF '.number_format((float) $monthPl['revenue'], 0),
                    'subtext' => $monthStart->isoFormat('MMM D').' – '.$today->isoFormat('MMM D'),
                ],
                'cogs' => [
                    'value' => 'RWF '.number_format((float) $monthPl['cogs'], 0),
                    'subtext' => __('Month to date'),
                ],
                'gross_margin_pct' => [
                    'value' => number_format((float) $monthPl['gross_margin_pct'], 1).'%',
                    'color' => $monthPl['gross_margin_pct'] >= 20 ? 'success' : ($monthPl['gross_margin_pct'] >= 10 ? 'warning' : 'danger'),
                    'subtext' => __('Gross margin'),
                ],
                'credit_outstanding' => [
                    'value' => 'RWF '.number_format($creditOutstanding, 0),
                    'color' => $creditOutstanding > 0 ? 'warning' : 'success',
                    'subtext' => __('Customer balances'),
                ],
            ],
            'operations' => [
                'open_cutting_sessions' => [
                    'value' => (string) $openSessions,
                    'color' => $openSessions > 0 ? 'warning' : 'success',
                    'subtext' => __('Sessions in progress'),
                ],
                'avg_yield' => [
                    'value' => $avgYield !== null ? number_format($avgYield, 1).'%' : '—',
                    'subtext' => __('Closed sessions (30d)'),
                ],
                'pending_deliveries' => [
                    'value' => (string) $pendingDeliveries,
                    'color' => $pendingDeliveries > 0 ? 'warning' : null,
                    'subtext' => __('POs awaiting delivery'),
                ],
                'open_orders' => [
                    'value' => (string) $openOrders,
                    'subtext' => __('Customer orders open'),
                ],
            ],
            'compliance_kpis' => [
                'hygiene_log' => $hygieneToday,
                'staff_health' => $staffHealthLabel,
                'permits_expiring' => [
                    'value' => (string) $complianceAlerts['expiring_permit_count'],
                    'color' => $complianceAlerts['expiring_permit_count'] > 0 ? 'warning' : 'success',
                    'subtext' => __('Within 60 days'),
                ],
                'audit_readiness' => [
                    'value' => number_format($auditReadiness, 0).'%',
                    'color' => $auditReadiness >= 80 ? 'success' : ($auditReadiness >= 50 ? 'warning' : 'danger'),
                    'subtext' => __('Hygiene pass rate (30d)'),
                ],
            ],
            'alerts' => $this->buildAlerts($business, $storageSummary, $complianceAlerts, $creditOutstanding),
            'stock_by_meat_type' => $this->stockByMeatType($business),
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
     * @return array{value: string, color: ?string, subtext: string}
     */
    private function tempStatusKpi(int $breachesToday): array
    {
        if ($breachesToday === 0) {
            return [
                'value' => __('OK'),
                'color' => 'success',
                'subtext' => __('No breaches today'),
            ];
        }

        return [
            'value' => (string) $breachesToday,
            'color' => 'danger',
            'subtext' => __('Temperature breach(es) today'),
        ];
    }

    private function averageYieldPct(Business $business): ?float
    {
        $sessions = $business->butcherCuttingSessions()
            ->where('status', ButcherCuttingSession::STATUS_CLOSED)
            ->whereDate('session_date', '>=', $this->today()->copy()->subDays(30)->toDateString())
            ->where('source_weight_kg', '>', 0)
            ->get(['source_weight_kg', 'total_cuts_weight_kg']);

        if ($sessions->isEmpty()) {
            return null;
        }

        $totalSource = (float) $sessions->sum('source_weight_kg');
        $totalCuts = (float) $sessions->sum('total_cuts_weight_kg');

        if ($totalSource <= 0) {
            return null;
        }

        return round(($totalCuts / $totalSource) * 100, 1);
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
            ];
        }

        if ($missing === 0) {
            return [
                'value' => __('Complete'),
                'color' => 'success',
                'subtext' => __('All outlets logged today'),
            ];
        }

        return [
            'value' => (string) $missing,
            'color' => 'warning',
            'subtext' => __('Outlet(s) missing today'),
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
            ];
        }

        return [
            'value' => (string) $count,
            'color' => 'warning',
            'subtext' => __('Expiring or expired'),
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
     * @param  array<string, mixed>  $storageSummary
     * @param  array<string, mixed>  $complianceAlerts
     * @return list<array{level: string, message: string}>
     */
    private function buildAlerts(
        Business $business,
        array $storageSummary,
        array $complianceAlerts,
        float $creditOutstanding,
    ): array {
        $alerts = [];

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

        if ($storageSummary['temp_breaches_today'] > 0) {
            $alerts[] = [
                'level' => 'danger',
                'message' => __(':count temperature breach(es) logged today', ['count' => $storageSummary['temp_breaches_today']]),
            ];
        }

        if ($storageSummary['expiring_soon'] > 0) {
            $alerts[] = [
                'level' => 'warning',
                'message' => __(':count batch(es) expiring within 24 hours', ['count' => $storageSummary['expiring_soon']]),
            ];
        }

        if ($storageSummary['expired_batches'] > 0) {
            $alerts[] = [
                'level' => 'danger',
                'message' => __(':count expired batch(es) in inventory', ['count' => $storageSummary['expired_batches']]),
            ];
        }

        $openSessions = (int) $business->butcherCuttingSessions()
            ->where('status', ButcherCuttingSession::STATUS_OPEN)
            ->count();

        if ($openSessions > 0) {
            $alerts[] = [
                'level' => 'info',
                'message' => __(':count open cutting session(s)', ['count' => $openSessions]),
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
     * @return list<array{meat_type: string, label: string, kg: float, pct: float}>
     */
    private function stockByMeatType(Business $business): array
    {
        $totals = $business->butcherInventoryBatches()
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->select('meat_type', DB::raw('SUM(remaining_weight_kg) as total_kg'))
            ->groupBy('meat_type')
            ->pluck('total_kg', 'meat_type');

        $grandTotal = (float) $totals->sum();

        $rows = [];
        foreach (self::STOCK_MEAT_TYPES as $type) {
            $kg = (float) ($totals[$type] ?? 0);
            $rows[] = [
                'meat_type' => $type,
                'label' => ucfirst($type),
                'kg' => $kg,
                'pct' => $grandTotal > 0 ? round(($kg / $grandTotal) * 100, 1) : 0.0,
            ];
        }

        return $rows;
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
