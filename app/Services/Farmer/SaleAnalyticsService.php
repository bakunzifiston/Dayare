<?php

namespace App\Services\Farmer;

use App\Models\Buyer;
use App\Models\Sale;
use App\Models\SalePayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleAnalyticsService
{
    /** @param  Collection<int, int>  $farmIds */
    public function metrics(Collection $farmIds): array
    {
        $salesQuery = Sale::query()->whereIn('farm_id', $farmIds);
        $completed = (clone $salesQuery)->where('sale_status', Sale::STATUS_COMPLETED);

        $topBuyer = Buyer::query()
            ->whereIn('id', (clone $completed)->select('buyer_id'))
            ->withCount(['sales as completed_sales_count' => fn ($q) => $q->where('sale_status', Sale::STATUS_COMPLETED)])
            ->orderByDesc('completed_sales_count')
            ->first();

        return [
            'total_sales' => (int) (clone $salesQuery)->count(),
            'completed_sales' => (int) (clone $completed)->count(),
            'revenue' => (float) (clone $completed)->sum('total_amount'),
            'animals_sold' => (int) DB::table('sale_animals')
                ->join('sales', 'sales.id', '=', 'sale_animals.sale_id')
                ->whereIn('sales.farm_id', $farmIds->all())
                ->where('sales.sale_status', Sale::STATUS_COMPLETED)
                ->whereNotNull('sale_animals.animal_id')
                ->count(),
            'pending_payments' => (int) (clone $salesQuery)->whereIn('payment_status', [Sale::PAYMENT_PENDING, Sale::PAYMENT_PARTIAL, Sale::PAYMENT_OVERDUE])->count(),
            'top_buyer' => $topBuyer?->buyer_name,
        ];
    }

    /** @param  Collection<int, int>  $farmIds */
    public function charts(Collection $farmIds): array
    {
        $start = now()->subMonths(5)->startOfMonth();

        $revenueTrend = Sale::query()
            ->whereIn('farm_id', $farmIds)
            ->where('sale_status', Sale::STATUS_COMPLETED)
            ->where('sale_date', '>=', $start)
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') as period, SUM(total_amount) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');

        $salesByType = Sale::query()
            ->whereIn('farm_id', $farmIds)
            ->where('sale_status', Sale::STATUS_COMPLETED)
            ->selectRaw('sale_type, COUNT(*) as total')
            ->groupBy('sale_type')
            ->pluck('total', 'sale_type');

        $paymentMethods = SalePayment::query()
            ->whereHas('sale', fn ($q) => $q->whereIn('farm_id', $farmIds))
            ->selectRaw('payment_method, SUM(amount_paid) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $buyerTotals = Sale::query()
            ->whereIn('farm_id', $farmIds)
            ->where('sale_status', Sale::STATUS_COMPLETED)
            ->selectRaw('buyer_id, SUM(total_amount) as total')
            ->groupBy('buyer_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                $buyer = Buyer::query()->find($row->buyer_id);

                return [
                    'label' => $buyer?->buyer_name ?? __('Unknown buyer'),
                    'value' => (float) $row->total,
                ];
            });

        return [
            'revenue_trend' => $this->lineChart($revenueTrend, __('Revenue')),
            'sales_by_type' => $this->barChart($salesByType, __('Sales')),
            'payment_methods' => $this->barChart($paymentMethods, __('Payments')),
            'buyer_analytics' => [
                'type' => 'bar',
                'labels' => $buyerTotals->pluck('label')->all(),
                'datasets' => [[
                    'label' => __('Buyer spend'),
                    'data' => $buyerTotals->pluck('value')->all(),
                    'backgroundColor' => '#0f766e',
                ]],
            ],
        ];
    }

    private function lineChart(Collection $rows, string $label): array
    {
        return [
            'type' => 'line',
            'labels' => $rows->keys()->all(),
            'datasets' => [[
                'label' => $label,
                'data' => $rows->values()->map(fn ($v) => (float) $v)->all(),
                'borderColor' => '#b91c1c',
                'backgroundColor' => 'rgba(185, 28, 28, 0.12)',
                'fill' => true,
                'tension' => 0.35,
            ]],
        ];
    }

    private function barChart(Collection $rows, string $label): array
    {
        return [
            'type' => 'bar',
            'labels' => $rows->keys()->map(fn ($key) => ucwords(str_replace('_', ' ', (string) $key)))->all(),
            'datasets' => [[
                'label' => $label,
                'data' => $rows->values()->map(fn ($v) => (float) $v)->all(),
                'backgroundColor' => '#1d4ed8',
            ]],
        ];
    }
}
