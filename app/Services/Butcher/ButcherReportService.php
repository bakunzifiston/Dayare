<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherSale;
use App\Models\ButcherStockCount;
use Carbon\Carbon;

class ButcherReportService
{
    public function __construct(
        private readonly ButcherStorageService $storage,
        private readonly ButcherCuttingService $cutting,
        private readonly ButcherProcurementService $procurement,
        private readonly ButcherFinanceService $finance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildHub(Business $business, Carbon $from, Carbon $to): array
    {
        $storage = $this->storage->getStorageSummary($business);
        $waste = $this->storage->getWasteSummary($business, '30d');
        $yield = $this->cutting->getYieldReport($business, '30d');
        $receiving = $this->procurement->getReceivingSummary($business, '30d');
        $finance = $this->finance->getFinanceSummary($business, $from, $to);

        $openCounts = (int) $business->butcherStockCounts()
            ->where('status', ButcherStockCount::STATUS_DRAFT)
            ->count();

        $salesCount = (int) $business->butcherSales()
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->count();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'kpis' => [
                'stock_kg' => (float) $storage['kg_in_storage'],
                'batches' => (int) $storage['batches_in_storage'],
                'received_kg' => (float) $receiving['received_weight_kg'],
                'yield_kg' => (float) $yield['total_yield_kg'],
                'waste_kg' => (float) $waste['waste_kg'],
                'sales_count' => $salesCount,
                'revenue' => (float) $finance['revenue'],
                'open_stock_counts' => $openCounts,
            ],
            'sections' => [
                [
                    'title' => __('Receiving'),
                    'description' => __('Deliveries, kg received, and spend.'),
                    'route' => 'butcher.receiving.index',
                    'stats' => [
                        __('Deliveries') => (string) $receiving['deliveries_total'],
                        __('Kg received') => number_format((float) $receiving['received_weight_kg'], 1).' kg',
                    ],
                ],
                [
                    'title' => __('Processing'),
                    'description' => __('Yield, wastage, and closed sessions.'),
                    'route' => 'butcher.processing.index',
                    'stats' => [
                        __('Yield') => number_format((float) $yield['total_yield_kg'], 1).' kg',
                        __('Avg wastage') => number_format((float) $yield['avg_wastage_pct'], 1).'%',
                    ],
                ],
                [
                    'title' => __('Inventory'),
                    'description' => __('On-hand batches and expiry risk.'),
                    'route' => 'butcher.inventory.index',
                    'stats' => [
                        __('Batches') => (string) $storage['batches_in_storage'],
                        __('Expiring soon') => (string) $storage['expiring_soon'],
                    ],
                ],
                [
                    'title' => __('Waste & adjustments'),
                    'description' => __('Disposals and inventory corrections.'),
                    'route' => 'butcher.waste.index',
                    'stats' => [
                        __('Waste') => number_format((float) $waste['waste_kg'], 1).' kg',
                        __('Adjustments') => (string) $waste['adjustment_events'],
                    ],
                ],
                [
                    'title' => __('Stock counts'),
                    'description' => __('Physical counts vs system stock.'),
                    'route' => 'butcher.stock-counts.index',
                    'stats' => [
                        __('Open counts') => (string) $openCounts,
                    ],
                ],
                [
                    'title' => __('Sales & finance'),
                    'description' => __('Revenue, COGS, and P&L.'),
                    'route' => 'butcher.finance.index',
                    'stats' => [
                        __('Sales') => (string) $salesCount,
                        __('Revenue') => 'RWF '.number_format((float) $finance['revenue'], 0),
                    ],
                ],
                [
                    'title' => __('Compliance'),
                    'description' => __('Hygiene, sanitation, and audit readiness.'),
                    'route' => 'butcher.compliance.index',
                    'stats' => [],
                ],
            ],
            'stock_by_meat' => $this->stockByMeatType($business),
        ];
    }

    /**
     * @return list<array{meat_type: string, label: string, kg: float}>
     */
    private function stockByMeatType(Business $business): array
    {
        $types = [
            ButcherInventoryBatch::MEAT_BEEF,
            ButcherInventoryBatch::MEAT_GOAT,
            ButcherInventoryBatch::MEAT_PORK,
            ButcherInventoryBatch::MEAT_POULTRY,
        ];

        $totals = $business->butcherInventoryBatches()
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->selectRaw('meat_type, SUM(remaining_weight_kg) as total_kg')
            ->groupBy('meat_type')
            ->pluck('total_kg', 'meat_type');

        $rows = [];
        foreach ($types as $type) {
            $rows[] = [
                'meat_type' => $type,
                'label' => ucfirst($type),
                'kg' => (float) ($totals[$type] ?? 0),
            ];
        }

        return $rows;
    }
}
