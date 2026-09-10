<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherComplianceOverride;
use App\Models\ButcherCuttingSession;
use App\Models\ButcherCuttingSessionSource;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use App\Models\ButcherSale;
use App\Models\ButcherStockCount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ButcherReportService
{
    public function __construct(
        private readonly ButcherStorageService $storage,
        private readonly ButcherCuttingService $cutting,
        private readonly ButcherProcurementService $procurement,
        private readonly ButcherFinanceService $finance,
        private readonly ButcherReceivablesService $receivables,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildHub(Business $business, Carbon $from, Carbon $to, ?int $outletId = null): array
    {
        $storage = $this->storage->getStorageSummary($business, $outletId);
        $waste = $this->storage->getWasteSummary($business, '30d');
        $yield = $this->cutting->getYieldReport($business, '30d');
        $receiving = $this->procurement->getReceivingSummary($business, '30d');
        $finance = $this->finance->getFinanceSummary($business, $from, $to);
        $aging = $this->receivables->agingReport($business);
        $overrideCount = (int) ButcherComplianceOverride::query()
            ->where('business_id', $business->id)
            ->count();

        $openCounts = (int) $business->butcherStockCounts()
            ->where('status', ButcherStockCount::STATUS_DRAFT)
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->count();

        $salesCount = (int) $business->butcherSales()
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->count();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'outlet_id' => $outletId,
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
                    'title' => __('Receivables'),
                    'description' => __('Customer outstanding balances by sale-date aging.'),
                    'route' => 'butcher.finance.receivables.index',
                    'stats' => [
                        __('Outstanding') => 'RWF '.number_format((float) $aging['totals']['outstanding'], 0),
                        __('60+ days') => 'RWF '.number_format((float) $aging['totals']['bucket_60_plus'], 0),
                    ],
                ],
                [
                    'title' => __('Batch traceability'),
                    'description' => __('Supplier → delivery → batch → cuts → sale → customer.'),
                    'route' => 'butcher.reports.traceability',
                    'stats' => [],
                ],
                [
                    'title' => __('Compliance overrides'),
                    'description' => __('Logged safety overrides for breached or expired stock.'),
                    'route' => 'butcher.reports.compliance-overrides',
                    'stats' => [
                        __('Overrides') => (string) $overrideCount,
                    ],
                ],
                [
                    'title' => __('Compliance'),
                    'description' => __('Hygiene, sanitation, and audit readiness.'),
                    'route' => 'butcher.compliance.index',
                    'stats' => [],
                ],
            ],
            'stock_by_meat' => $this->stockByMeatType($business, $outletId),
        ];
    }

    /**
     * Resolve a batch or sale number into a structured traceability chain.
     *
     * @return array<string, mixed>
     */
    public function resolveTraceability(Business $business, ?string $query): array
    {
        $query = trim((string) $query);
        if ($query === '') {
            return [
                'query' => '',
                'found' => false,
                'mode' => null,
                'chain' => [],
                'error' => null,
            ];
        }

        $batch = $business->butcherInventoryBatches()
            ->where('batch_number', $query)
            ->first();

        if ($batch !== null) {
            $payload = $this->batchTraceability($business, (int) $batch->id);

            return array_merge($payload, [
                'query' => $query,
                'found' => true,
                'error' => null,
            ]);
        }

        $sale = $business->butcherSales()
            ->where('sale_number', $query)
            ->first();

        if ($sale !== null) {
            $payload = $this->saleTraceability($business, (int) $sale->id);

            return array_merge($payload, [
                'query' => $query,
                'found' => true,
                'error' => null,
            ]);
        }

        return [
            'query' => $query,
            'found' => false,
            'mode' => null,
            'chain' => [],
            'error' => __('No batch or sale found for :query in this business.', ['query' => $query]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function batchTraceability(Business $business, int $batchId): array
    {
        $batch = $business->butcherInventoryBatches()
            ->with([
                'outlet',
                'delivery.supplier',
                'deliveryLine',
            ])
            ->find($batchId);

        if ($batch === null) {
            throw ValidationException::withMessages([
                'query' => [__('Batch not found for this business.')],
            ]);
        }

        $sessionIds = $this->sessionIdsForBatch((int) $batch->id);
        $sessions = ButcherCuttingSession::query()
            ->where('business_id', $business->id)
            ->whereIn('id', $sessionIds)
            ->with(['sources.batch', 'cutOutputs.cutType', 'cutOutputs.saleItems.sale.customer', 'cutOutputs.saleItems.product'])
            ->orderBy('id')
            ->get();

        $chain = $this->buildUpstreamChain($batch);

        foreach ($sessions as $session) {
            $chain[] = [
                'step' => 'cutting_session',
                'label' => __('Cutting session'),
                'reference' => $session->session_number,
                'detail' => __(
                    ':status · source :source kg · yield :yield kg · waste :waste kg',
                    [
                        'status' => $session->status,
                        'source' => number_format((float) $session->totalSourceWeightKg(), 1),
                        'yield' => number_format((float) ($session->total_cuts_weight_kg ?? 0), 1),
                        'waste' => number_format((float) ($session->wastage_kg ?? 0), 1),
                    ]
                ),
                'meta' => [
                    'session_id' => $session->id,
                    'status' => $session->status,
                ],
            ];

            foreach ($session->cutOutputs as $output) {
                $chain[] = [
                    'step' => 'cut_output',
                    'label' => __('Cut output'),
                    'reference' => $output->cutType?->name ?? __('Cut #'.$output->id),
                    'detail' => __(
                        ':weight kg cut · :remaining kg remaining',
                        [
                            'weight' => number_format((float) $output->weight_kg, 1),
                            'remaining' => number_format((float) $output->remaining_weight_kg, 1),
                        ]
                    ),
                    'meta' => [
                        'cut_output_id' => $output->id,
                        'session_id' => $session->id,
                    ],
                ];

                foreach ($output->saleItems as $item) {
                    $sale = $item->sale;
                    if ($sale === null || (int) $sale->business_id !== (int) $business->id) {
                        continue;
                    }

                    $chain[] = [
                        'step' => 'sale',
                        'label' => __('Sale'),
                        'reference' => $sale->sale_number,
                        'detail' => __(
                            ':qty kg · :product · customer :customer',
                            [
                                'qty' => number_format((float) $item->quantity_kg, 1),
                                'product' => $item->product?->name ?? __('Product'),
                                'customer' => $sale->customer?->name ?? __('Walk-in'),
                            ]
                        ),
                        'meta' => [
                            'sale_id' => $sale->id,
                            'customer_id' => $sale->customer_id,
                            'cut_output_id' => $output->id,
                        ],
                    ];
                }
            }
        }

        return [
            'mode' => 'batch',
            'batch' => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'meat_type' => $batch->meat_type,
                'status' => $batch->status,
                'remaining_weight_kg' => (float) $batch->remaining_weight_kg,
                'temperature_breach' => (bool) $batch->temperature_breach,
            ],
            'chain' => $chain,
            'movements' => $this->movementRows(
                ButcherInventoryMovement::query()
                    ->where('business_id', $business->id)
                    ->where(function ($q) use ($batch, $sessions) {
                        $q->where('batch_id', $batch->id);
                        $cutOutputIds = $sessions->flatMap->cutOutputs->pluck('id')->filter()->all();
                        if ($cutOutputIds !== []) {
                            $q->orWhereIn('cut_output_id', $cutOutputIds);
                        }
                    })
                    ->orderBy('occurred_at')
                    ->orderBy('id')
                    ->limit(200)
                    ->get()
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function saleTraceability(Business $business, int $saleId): array
    {
        $sale = $business->butcherSales()
            ->with([
                'customer',
                'outlet',
                'items.product',
                'items.cutOutput.cutType',
                'items.cutOutput.session.sources.batch.delivery.supplier',
                'items.cutOutput.session.sources.batch.deliveryLine',
                'items.cutOutput.session.batch.delivery.supplier',
                'items.cutOutput.session.batch.deliveryLine',
            ])
            ->find($saleId);

        if ($sale === null) {
            throw ValidationException::withMessages([
                'query' => [__('Sale not found for this business.')],
            ]);
        }

        $chain = [
            [
                'step' => 'sale',
                'label' => __('Sale'),
                'reference' => $sale->sale_number,
                'detail' => __(
                    ':date · :method · total RWF :total · customer :customer',
                    [
                        'date' => $sale->sale_date?->toDateString() ?? '—',
                        'method' => $sale->payment_method,
                        'total' => number_format((float) $sale->total_amount, 0),
                        'customer' => $sale->customer?->name ?? __('Walk-in'),
                    ]
                ),
                'meta' => [
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                ],
            ],
        ];

        $seenBatches = [];

        foreach ($sale->items as $item) {
            $output = $item->cutOutput;
            $chain[] = [
                'step' => 'sale_item',
                'label' => __('Sale line'),
                'reference' => $item->product?->name ?? __('Line #'.$item->id),
                'detail' => __(
                    ':qty kg @ RWF :price',
                    [
                        'qty' => number_format((float) $item->quantity_kg, 1),
                        'price' => number_format((float) $item->unit_price, 0),
                    ]
                ),
                'meta' => [
                    'sale_item_id' => $item->id,
                    'cut_output_id' => $item->cut_output_id,
                ],
            ];

            if ($output === null) {
                continue;
            }

            $session = $output->session;
            $chain[] = [
                'step' => 'cut_output',
                'label' => __('Cut output'),
                'reference' => $output->cutType?->name ?? __('Cut #'.$output->id),
                'detail' => __(
                    ':weight kg from session :session',
                    [
                        'weight' => number_format((float) $output->weight_kg, 1),
                        'session' => $session?->session_number ?? '—',
                    ]
                ),
                'meta' => [
                    'cut_output_id' => $output->id,
                    'session_id' => $session?->id,
                ],
            ];

            if ($session !== null) {
                $chain[] = [
                    'step' => 'cutting_session',
                    'label' => __('Cutting session'),
                    'reference' => $session->session_number,
                    'detail' => __('Status :status', ['status' => $session->status]),
                    'meta' => ['session_id' => $session->id],
                ];

                $sourceBatches = $session->sources->isNotEmpty()
                    ? $session->sources->map->batch->filter()
                    : collect([$session->batch])->filter();

                foreach ($sourceBatches as $batch) {
                    if (isset($seenBatches[$batch->id])) {
                        continue;
                    }
                    $seenBatches[$batch->id] = true;

                    foreach ($this->buildUpstreamChain($batch) as $step) {
                        $chain[] = $step;
                    }
                }
            }
        }

        return [
            'mode' => 'sale',
            'sale' => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'customer_name' => $sale->customer?->name,
                'total_amount' => (float) $sale->total_amount,
                'sale_date' => $sale->sale_date?->toDateString(),
            ],
            'chain' => $chain,
            'movements' => $this->movementRows(
                ButcherInventoryMovement::query()
                    ->where('business_id', $business->id)
                    ->where('reference_type', ButcherSale::class)
                    ->where('reference_id', $sale->id)
                    ->orderBy('occurred_at')
                    ->orderBy('id')
                    ->get()
            ),
        ];
    }

    /**
     * @return array{
     *     from: string|null,
     *     to: string|null,
     *     total: int,
     *     rows: list<array<string, mixed>>
     * }
     */
    public function complianceOverridesReport(Business $business, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = ButcherComplianceOverride::query()
            ->where('business_id', $business->id)
            ->with(['batch', 'cutOutput.cutType', 'overriddenByUser'])
            ->orderByDesc('overridden_at')
            ->orderByDesc('id');

        if ($from !== null) {
            $query->whereDate('overridden_at', '>=', $from->toDateString());
        }
        if ($to !== null) {
            $query->whereDate('overridden_at', '<=', $to->toDateString());
        }

        $rows = $query->limit(500)->get()->map(function (ButcherComplianceOverride $row) {
            return [
                'id' => $row->id,
                'context_type' => $row->context_type,
                'context_id' => $row->context_id,
                'batch_number' => $row->batch?->batch_number,
                'cut_output_id' => $row->cut_output_id,
                'cut_type' => $row->cutOutput?->cutType?->name,
                'issues' => $row->issues ?? [],
                'reason' => $row->reason,
                'overridden_by' => $row->overriddenByUser?->name ?? __('Unknown'),
                'overridden_at' => $row->overridden_at?->format('Y-m-d H:i'),
            ];
        })->all();

        return [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'total' => count($rows),
            'rows' => $rows,
        ];
    }

    /**
     * @return list<array{meat_type: string, label: string, kg: float}>
     */
    private function stockByMeatType(Business $business, ?int $outletId = null): array
    {
        // Stock-on-hand continues to read remaining_weight_kg (Phase 3 source of truth).
        // Ledger agreement is enforced by ButcherInventoryReconciliationService — not recomputed here.
        $types = [
            ButcherInventoryBatch::MEAT_BEEF,
            ButcherInventoryBatch::MEAT_GOAT,
            ButcherInventoryBatch::MEAT_PORK,
            ButcherInventoryBatch::MEAT_POULTRY,
        ];

        $totals = $business->butcherInventoryBatches()
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
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

    /**
     * @return list<int>
     */
    private function sessionIdsForBatch(int $batchId): array
    {
        $fromPrimary = ButcherCuttingSession::query()
            ->where('batch_id', $batchId)
            ->pluck('id');

        $fromSources = ButcherCuttingSessionSource::query()
            ->where('batch_id', $batchId)
            ->pluck('session_id');

        return $fromPrimary->merge($fromSources)->unique()->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildUpstreamChain(ButcherInventoryBatch $batch): array
    {
        $batch->loadMissing(['delivery.supplier', 'deliveryLine', 'outlet']);
        $chain = [];

        $supplier = $batch->delivery?->supplier;
        if ($supplier !== null) {
            $chain[] = [
                'step' => 'supplier',
                'label' => __('Supplier'),
                'reference' => $supplier->name,
                'detail' => $supplier->supplier_type ?? '',
                'meta' => ['supplier_id' => $supplier->id],
            ];
        }

        $delivery = $batch->delivery;
        if ($delivery !== null) {
            $chain[] = [
                'step' => 'delivery',
                'label' => __('Delivery'),
                'reference' => $delivery->delivery_number,
                'detail' => __(
                    ':meat · :weight kg received',
                    [
                        'meat' => $delivery->meat_type ?? $batch->meat_type,
                        'weight' => number_format((float) ($delivery->received_weight_kg ?? $batch->initial_weight_kg), 1),
                    ]
                ),
                'meta' => ['delivery_id' => $delivery->id],
            ];
        }

        if ($batch->deliveryLine !== null) {
            $line = $batch->deliveryLine;
            $chain[] = [
                'step' => 'delivery_line',
                'label' => __('Delivery line'),
                'reference' => '#'.$line->id,
                'detail' => __(
                    ':meat · :weight kg',
                    [
                        'meat' => $line->meat_type ?? $batch->meat_type,
                        'weight' => number_format((float) ($line->received_weight_kg ?? $batch->initial_weight_kg), 1),
                    ]
                ),
                'meta' => ['delivery_line_id' => $line->id],
            ];
        }

        $chain[] = [
            'step' => 'batch',
            'label' => __('Inventory batch'),
            'reference' => $batch->batch_number,
            'detail' => __(
                ':meat · :remaining / :initial kg · :status · outlet :outlet',
                [
                    'meat' => $batch->meat_type,
                    'remaining' => number_format((float) $batch->remaining_weight_kg, 1),
                    'initial' => number_format((float) $batch->initial_weight_kg, 1),
                    'status' => $batch->status,
                    'outlet' => $batch->outlet?->name ?? '—',
                ]
            ),
            'meta' => [
                'batch_id' => $batch->id,
                'temperature_breach' => (bool) $batch->temperature_breach,
            ],
        ];

        return $chain;
    }

    /**
     * @param  Collection<int, ButcherInventoryMovement>  $movements
     * @return list<array<string, mixed>>
     */
    private function movementRows(Collection $movements): array
    {
        return $movements->map(function (ButcherInventoryMovement $m) {
            return [
                'id' => $m->id,
                'type' => $m->type,
                'quantity_kg' => (float) $m->quantity_kg,
                'batch_id' => $m->batch_id,
                'cut_output_id' => $m->cut_output_id,
                'occurred_at' => $m->occurred_at?->format('Y-m-d H:i'),
                'reference_type' => $m->reference_type,
                'reference_id' => $m->reference_id,
            ];
        })->all();
    }
}
