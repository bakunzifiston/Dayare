<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherExpense;
use App\Models\ButcherSale;
use App\Models\ButcherSaleItem;
use App\Models\User;
use App\Support\DomPdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ButcherFinanceService
{
    public function logExpense(Business $business, array $data, User $user): ButcherExpense
    {
        return $business->butcherExpenses()->create([
            'outlet_id' => $data['outlet_id'] ?? null,
            'category' => (string) $data['category'],
            'description' => (string) $data['description'],
            'amount' => (float) $data['amount'],
            'expense_date' => Carbon::parse($data['expense_date'])->toDateString(),
            'payment_method' => (string) $data['payment_method'],
            'receipt_path' => $data['receipt_path'] ?? null,
            'recorded_by' => $user->id,
        ]);
    }

    public function updateExpense(ButcherExpense $expense, array $data): void
    {
        $expense->update([
            'outlet_id' => $data['outlet_id'] ?? $expense->outlet_id,
            'category' => $data['category'] ?? $expense->category,
            'description' => $data['description'] ?? $expense->description,
            'amount' => $data['amount'] ?? $expense->amount,
            'expense_date' => isset($data['expense_date'])
                ? Carbon::parse($data['expense_date'])->toDateString()
                : $expense->expense_date,
            'payment_method' => $data['payment_method'] ?? $expense->payment_method,
            'receipt_path' => $data['receipt_path'] ?? $expense->receipt_path,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getProfitAndLoss(Business $business, Carbon $from, Carbon $to): array
    {
        $revenue = $this->getRevenue($business, $from, $to);
        $cogs = $this->getCOGS($business, $from, $to);
        $grossProfit = round($revenue - $cogs, 2);
        $operatingExpenses = $this->getOperatingExpenses($business, $from, $to);
        $netProfit = round($grossProfit - $operatingExpenses, 2);
        $netMarginPct = $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0.0;
        $grossMarginPct = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0.0;

        $expensesByCategory = $business->butcherExpenses()
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin_pct' => $grossMarginPct,
            'operating_expenses' => $operatingExpenses,
            'expenses_by_category' => $expensesByCategory,
            'net_profit' => $netProfit,
            'net_margin_pct' => $netMarginPct,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCashFlow(Business $business, Carbon $from, Carbon $to): array
    {
        $sales = $business->butcherSales()
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->get();

        $expenses = $business->butcherExpenses()
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->get();

        $days = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $days[$key] = ['date' => $key, 'cash_in' => 0.0, 'cash_out' => 0.0, 'net' => 0.0];
            $cursor->addDay();
        }

        foreach ($sales as $sale) {
            $key = $sale->sale_date?->toDateString();
            if ($key && isset($days[$key])) {
                $days[$key]['cash_in'] += (float) $sale->amount_paid;
            }
        }

        foreach ($expenses as $expense) {
            $key = $expense->expense_date?->toDateString();
            if ($key && isset($days[$key])) {
                $days[$key]['cash_out'] += (float) $expense->amount;
            }
        }

        foreach ($days as &$day) {
            $day['cash_in'] = round($day['cash_in'], 2);
            $day['cash_out'] = round($day['cash_out'], 2);
            $day['net'] = round($day['cash_in'] - $day['cash_out'], 2);
        }
        unset($day);

        $totalIn = round(collect($days)->sum('cash_in'), 2);
        $totalOut = round(collect($days)->sum('cash_out'), 2);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days' => array_values($days),
            'total_cash_in' => $totalIn,
            'total_cash_out' => $totalOut,
            'net_cash_flow' => round($totalIn - $totalOut, 2),
            'sales_by_payment' => $sales->groupBy('payment_method')->map(fn ($g) => round((float) $g->sum('amount_paid'), 2)),
            'expenses_by_payment' => $expenses->groupBy('payment_method')->map(fn ($g) => round((float) $g->sum('amount'), 2)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSalesReport(Business $business, string $groupBy, Carbon $from, Carbon $to): array
    {
        $sales = $business->butcherSales()
            ->with(['customer', 'outlet', 'items.product'])
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->orderBy('sale_date')
            ->get();

        $grouped = match ($groupBy) {
            'week' => $this->groupSalesByPeriod($sales, 'Y-\\WW'),
            'month' => $this->groupSalesByPeriod($sales, 'Y-m'),
            'product' => $this->groupSalesByProduct($sales),
            'outlet' => $this->groupSalesByOutlet($sales),
            'customer' => $this->groupSalesByCustomer($sales),
            default => $this->groupSalesByPeriod($sales, 'Y-m-d'),
        };

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group_by' => $groupBy,
            'total_revenue' => round((float) $sales->sum('total_amount'), 2),
            'total_sales' => $sales->count(),
            'groups' => $grouped,
        ];
    }

    public function getCOGS(Business $business, Carbon $from, Carbon $to): float
    {
        $items = ButcherSaleItem::query()
            ->whereHas('sale', function ($query) use ($business, $from, $to) {
                $query->where('business_id', $business->id)
                    ->where('status', ButcherSale::STATUS_COMPLETED)
                    ->whereDate('sale_date', '>=', $from->toDateString())
                    ->whereDate('sale_date', '<=', $to->toDateString());
            })
            ->with('cutOutput')
            ->get();

        $total = $items->sum(function (ButcherSaleItem $item) {
            $qtyKg = (float) $item->quantity_kg;
            if ($qtyKg <= 0) {
                return 0;
            }

            $unitCost = (float) ($item->cutOutput?->unit_cost_per_kg ?? 0);

            return $qtyKg * $unitCost;
        });

        return round($total, 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFinanceSummary(Business $business, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();

        $pl = $this->getProfitAndLoss($business, $from, $to);

        return array_merge($pl, [
            'recent_expenses' => $business->butcherExpenses()
                ->with(['outlet', 'recordedByUser'])
                ->latest('expense_date')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    public function exportReport(Business $business, string $type, Carbon $from, Carbon $to, string $format): string
    {
        $data = match ($type) {
            'sales' => $this->getSalesReport($business, 'day', $from, $to),
            'pl' => $this->getProfitAndLoss($business, $from, $to),
            'cashflow' => $this->getCashFlow($business, $from, $to),
            'expenses' => $this->getExpensesExportData($business, $from, $to),
            default => $this->getProfitAndLoss($business, $from, $to),
        };

        $basename = sprintf('butcher-finance/%d/%s-%s-%s', $business->id, $type, $from->format('Ymd'), $to->format('Ymd'));

        return match ($format) {
            'pdf' => $this->exportPdf($business, $type, $data, $from, $to, $basename),
            'xlsx' => $this->exportSpreadsheet($business, $type, $data, $from, $to, $basename),
            default => $this->exportCsv($business, $type, $data, $from, $to, $basename),
        };
    }

    public function getRevenue(Business $business, Carbon $from, Carbon $to): float
    {
        return round((float) $business->butcherSales()
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->sum('total_amount'), 2);
    }

    public function getOperatingExpenses(Business $business, Carbon $from, Carbon $to): float
    {
        return round((float) $business->butcherExpenses()
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->sum('amount'), 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpensesExportData(Business $business, Carbon $from, Carbon $to): array
    {
        $expenses = $business->butcherExpenses()
            ->with(['outlet', 'recordedByUser'])
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->orderBy('expense_date')
            ->get();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'expenses' => $expenses,
            'total' => round((float) $expenses->sum('amount'), 2),
        ];
    }

    /**
     * @param  Collection<int, ButcherSale>  $sales
     * @return list<array<string, mixed>>
     */
    private function groupSalesByPeriod(Collection $sales, string $format): array
    {
        return $sales->groupBy(fn (ButcherSale $sale) => $sale->sale_date?->format($format) ?? 'unknown')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'sales_count' => $group->count(),
                'revenue' => round((float) $group->sum('total_amount'), 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ButcherSale>  $sales
     * @return list<array<string, mixed>>
     */
    private function groupSalesByProduct(Collection $sales): array
    {
        $totals = [];

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $name = $item->product?->name ?? __('Unknown');
                $totals[$name] = ($totals[$name] ?? 0) + (float) $item->subtotal;
            }
        }

        return collect($totals)->map(fn ($revenue, $label) => [
            'label' => $label,
            'sales_count' => null,
            'revenue' => round($revenue, 2),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, ButcherSale>  $sales
     * @return list<array<string, mixed>>
     */
    private function groupSalesByOutlet(Collection $sales): array
    {
        return $sales->groupBy(fn (ButcherSale $sale) => $sale->outlet?->name ?? __('Unknown'))
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'sales_count' => $group->count(),
                'revenue' => round((float) $group->sum('total_amount'), 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ButcherSale>  $sales
     * @return list<array<string, mixed>>
     */
    private function groupSalesByCustomer(Collection $sales): array
    {
        return $sales->groupBy(fn (ButcherSale $sale) => $sale->customer?->name ?? __('Walk-in'))
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'sales_count' => $group->count(),
                'revenue' => round((float) $group->sum('total_amount'), 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function exportCsv(Business $business, string $type, array $data, Carbon $from, Carbon $to, string $basename): string
    {
        $path = $basename.'.csv';
        $lines = $this->buildCsvLines($business, $type, $data, $from, $to);
        Storage::disk('public')->put($path, implode("\n", $lines));

        return $path;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function exportSpreadsheet(Business $business, string $type, array $data, Carbon $from, Carbon $to, string $basename): string
    {
        $path = $basename.'.xls';
        $rows = $this->buildCsvLines($business, $type, $data, $from, $to);
        $html = '<html><head><meta charset="UTF-8"></head><body><table border="1">';
        foreach ($rows as $line) {
            $html .= '<tr>';
            foreach (str_getcsv($line) as $cell) {
                $html .= '<td>'.htmlspecialchars($cell).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table></body></html>';
        Storage::disk('public')->put($path, $html);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function exportPdf(Business $business, string $type, array $data, Carbon $from, Carbon $to, string $basename): string
    {
        $path = $basename.'.pdf';
        $pdf = DomPdf::loadView('butcher.finance.reports.export-pdf', [
            'business' => $business,
            'type' => $type,
            'data' => $data,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ])->setPaper('a4', 'portrait');

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function buildCsvLines(Business $business, string $type, array $data, Carbon $from, Carbon $to): array
    {
        $lines = [
            'BuchaPro Butcher Finance Report',
            'Business,'.$this->csvEscape((string) $business->business_name),
            'Report,'.$type,
            'Period,'.$from->toDateString().' to '.$to->toDateString(),
            'Generated,'.now()->toDateTimeString(),
            '',
        ];

        if ($type === 'pl') {
            $lines[] = 'Line,Amount (RWF)';
            $lines[] = 'Revenue,'.$data['revenue'];
            $lines[] = 'COGS,'.$data['cogs'];
            $lines[] = 'Gross Profit,'.$data['gross_profit'];
            $lines[] = 'Operating Expenses,'.$data['operating_expenses'];
            $lines[] = 'Net Profit,'.$data['net_profit'];
            $lines[] = 'Net Margin %,'.$data['net_margin_pct'];
        } elseif ($type === 'sales') {
            $lines[] = 'Group,Sales Count,Revenue (RWF)';
            foreach ($data['groups'] as $group) {
                $lines[] = implode(',', [
                    $this->csvEscape((string) $group['label']),
                    $group['sales_count'] ?? '',
                    $group['revenue'],
                ]);
            }
        } elseif ($type === 'cashflow') {
            $lines[] = 'Date,Cash In,Cash Out,Net';
            foreach ($data['days'] as $day) {
                $lines[] = implode(',', [$day['date'], $day['cash_in'], $day['cash_out'], $day['net']]);
            }
        } elseif ($type === 'expenses') {
            $lines[] = 'Date,Category,Description,Amount,Payment,Outlet';
            foreach ($data['expenses'] as $expense) {
                $lines[] = implode(',', [
                    $expense->expense_date?->toDateString(),
                    $expense->category,
                    $this->csvEscape($expense->description),
                    $expense->amount,
                    $expense->payment_method,
                    $this->csvEscape($expense->outlet?->name ?? ''),
                ]);
            }
        }

        return $lines;
    }

    private function csvEscape(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
