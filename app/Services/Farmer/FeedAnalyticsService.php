<?php

namespace App\Services\Farmer;

use App\Models\FeedInventory;
use App\Models\FeedType;
use App\Models\FeedingRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FeedAnalyticsService
{
    /**
     * @param  Collection<int, int>  $businessIds
     * @return array<string, mixed>
     */
    public function metrics(Collection $businessIds): array
    {
        $today = Carbon::today();
        $feedTypeIds = FeedType::query()->whereIn('business_id', $businessIds)->pluck('id');

        $totalStock = (float) FeedInventory::query()
            ->whereIn('feed_type_id', $feedTypeIds)
            ->sum('quantity_remaining');

        $lowStockAlerts = FeedInventory::query()
            ->whereIn('feed_type_id', $feedTypeIds)
            ->where('status', FeedInventory::STATUS_LOW_STOCK)
            ->count();

        $dailyUsage = (float) FeedingRecord::query()
            ->whereIn('feed_type_id', $feedTypeIds)
            ->whereDate('feeding_date', $today)
            ->sum('quantity');

        $dailyCost = (float) FeedingRecord::query()
            ->whereIn('feeding_records.feed_type_id', $feedTypeIds)
            ->whereDate('feeding_records.feeding_date', $today)
            ->join('feed_inventories', 'feeding_records.feed_inventory_id', '=', 'feed_inventories.id')
            ->sum(DB::raw('feeding_records.quantity * COALESCE(feed_inventories.unit_cost, 0)'));

        $mostUsed = FeedingRecord::query()
            ->whereIn('feed_type_id', $feedTypeIds)
            ->whereDate('feeding_date', '>=', $today->copy()->subDays(30))
            ->select('feed_type_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('feed_type_id')
            ->orderByDesc('total')
            ->with('feedType')
            ->first();

        $wastage = (float) DB::table('feed_inventory_movements')
            ->join('feed_inventories', 'feed_inventory_movements.feed_inventory_id', '=', 'feed_inventories.id')
            ->whereIn('feed_inventories.feed_type_id', $feedTypeIds)
            ->where('feed_inventory_movements.movement_type', 'wastage')
            ->whereDate('feed_inventory_movements.created_at', '>=', $today->copy()->subDays(30))
            ->sum(DB::raw('ABS(feed_inventory_movements.quantity_change)'));

        return [
            'total_stock' => $totalStock,
            'low_stock_alerts' => $lowStockAlerts,
            'daily_usage' => $dailyUsage,
            'feed_cost_today' => round($dailyCost, 2),
            'most_used_feed' => $mostUsed?->feedType?->feed_name,
            'feed_wastage' => $wastage,
        ];
    }

    /**
     * @param  Collection<int, int>  $businessIds
     * @return array<string, array<string, mixed>>
     */
    public function charts(Collection $businessIds): array
    {
        $feedTypeIds = FeedType::query()->whereIn('business_id', $businessIds)->pluck('id');
        $months = collect(range(5, 0))->map(fn (int $offset) => Carbon::today()->startOfMonth()->subMonths($offset));
        $labels = $months->map(fn (Carbon $month) => $month->format('M Y'))->all();

        $usageTrend = $months->map(function (Carbon $month) use ($feedTypeIds): float {
            return (float) FeedingRecord::query()
                ->whereIn('feed_type_id', $feedTypeIds)
                ->whereBetween('feeding_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum('quantity');
        })->all();

        $inventoryTrend = $months->map(function (Carbon $month) use ($feedTypeIds): float {
            return (float) FeedInventory::query()
                ->whereIn('feed_type_id', $feedTypeIds)
                ->whereDate('purchase_date', '<=', $month->copy()->endOfMonth())
                ->sum('quantity_remaining');
        })->all();

        $costTrend = $months->map(function (Carbon $month) use ($feedTypeIds): float {
            return (float) FeedingRecord::query()
                ->whereIn('feeding_records.feed_type_id', $feedTypeIds)
                ->whereBetween('feeding_records.feeding_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->join('feed_inventories', 'feeding_records.feed_inventory_id', '=', 'feed_inventories.id')
                ->selectRaw('SUM(feeding_records.quantity * COALESCE(feed_inventories.unit_cost, 0)) as total')
                ->value('total');
        })->all();

        $byLivestock = FeedingRecord::query()
            ->whereIn('feed_type_id', $feedTypeIds)
            ->whereNotNull('livestock_id')
            ->select('livestock_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('livestock_id')
            ->orderByDesc('total')
            ->limit(6)
            ->with('livestock')
            ->get();

        return [
            'feed_usage_trend' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => __('Feed usage'),
                    'data' => $usageTrend,
                    'backgroundColor' => 'rgba(37, 99, 235, 0.55)',
                ]],
            ],
            'inventory_trend' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => __('Inventory remaining'),
                    'data' => $inventoryTrend,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.55)',
                ]],
            ],
            'feed_cost_trend' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => __('Feed cost'),
                    'data' => $costTrend,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.55)',
                ]],
            ],
            'consumption_by_livestock' => [
                'labels' => $byLivestock->map(fn ($row) => $row->livestock?->livestock_name ?: __('Group #:id', ['id' => $row->livestock_id]))->all(),
                'datasets' => [[
                    'label' => __('Consumption'),
                    'data' => $byLivestock->pluck('total')->map(fn ($value) => (float) $value)->all(),
                    'backgroundColor' => 'rgba(99, 102, 241, 0.55)',
                ]],
            ],
        ];
    }
}
