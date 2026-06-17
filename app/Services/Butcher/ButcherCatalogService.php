<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherCutOutput;
use App\Models\ButcherCuttingSession;
use App\Models\ButcherPriceRule;
use App\Models\ButcherProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ButcherCatalogService
{
    public function createProduct(Business $business, array $data): ButcherProduct
    {
        $product = $business->butcherProducts()->create([
            'cut_type_id' => $data['cut_type_id'] ?? null,
            'name' => (string) $data['name'],
            'meat_type' => (string) $data['meat_type'],
            'unit' => (string) ($data['unit'] ?? ButcherProduct::UNIT_PER_KG),
            'default_price' => (float) $data['default_price'],
            'avg_cost_per_kg' => (float) ($data['avg_cost_per_kg'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        if ($product->cut_type_id) {
            $this->recalculateAvgCost($product);
        } else {
            $this->recalculateMargin($product);
        }

        return $product->fresh();
    }

    public function updateProduct(ButcherProduct $product, array $data): void
    {
        $cutTypeChanged = array_key_exists('cut_type_id', $data)
            && (int) ($data['cut_type_id'] ?? 0) !== (int) $product->cut_type_id;

        $product->fill([
            'cut_type_id' => $data['cut_type_id'] ?? $product->cut_type_id,
            'name' => $data['name'] ?? $product->name,
            'meat_type' => $data['meat_type'] ?? $product->meat_type,
            'unit' => $data['unit'] ?? $product->unit,
            'default_price' => $data['default_price'] ?? $product->default_price,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $product->is_active,
        ]);
        $product->save();

        if ($cutTypeChanged && $product->cut_type_id) {
            $this->recalculateAvgCost($product);
        } else {
            $this->recalculateMargin($product);
        }
    }

    public function recalculateAvgCost(ButcherProduct $product): void
    {
        if ($product->cut_type_id === null) {
            $this->recalculateMargin($product);

            return;
        }

        $from = now()->subDays(30)->startOfDay();
        $outputs = ButcherCutOutput::query()
            ->where('business_id', $product->business_id)
            ->where('cut_type_id', $product->cut_type_id)
            ->whereHas('session', function ($query) use ($from) {
                $query->where('status', ButcherCuttingSession::STATUS_CLOSED)
                    ->where('closed_at', '>=', $from);
            })
            ->get(['weight_kg', 'unit_cost_per_kg']);

        $totalWeight = (float) $outputs->sum('weight_kg');

        if ($totalWeight > 0) {
            $weightedCost = $outputs->sum(fn (ButcherCutOutput $output) => (float) $output->weight_kg * (float) $output->unit_cost_per_kg);
            $product->avg_cost_per_kg = round($weightedCost / $totalWeight, 2);
        }

        $this->recalculateMargin($product);
    }

    public function recalculateProductsAfterSessionClose(ButcherCuttingSession $session): void
    {
        $cutTypeIds = $session->cutOutputs()->pluck('cut_type_id')->unique()->filter();

        if ($cutTypeIds->isEmpty()) {
            return;
        }

        ButcherProduct::query()
            ->where('business_id', $session->business_id)
            ->whereIn('cut_type_id', $cutTypeIds)
            ->each(fn (ButcherProduct $product) => $this->recalculateAvgCost($product));
    }

    public function setPriceRule(Business $business, array $data): ButcherPriceRule
    {
        return $business->butcherPriceRules()->create([
            'product_id' => (int) $data['product_id'],
            'outlet_id' => $data['outlet_id'] ?? null,
            'customer_tier' => $data['customer_tier'] ?? null,
            'price' => (float) $data['price'],
            'valid_from' => Carbon::parse($data['valid_from'])->toDateString(),
            'valid_until' => isset($data['valid_until']) && $data['valid_until'] !== ''
                ? Carbon::parse($data['valid_until'])->toDateString()
                : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    public function resolvePrice(ButcherProduct $product, ?int $outletId = null, ?string $tier = null, ?Carbon $on = null): float
    {
        if ($outletId === null && ($tier === null || $tier === '')) {
            return (float) $product->default_price;
        }

        $date = $on ?? now();
        $rules = $product->priceRules()
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', $date->toDateString());
            })
            ->get();

        $matched = $this->pickBestPriceRule($rules, $outletId, $tier);

        return $matched !== null
            ? (float) $matched->price
            : (float) $product->default_price;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCatalogSummary(Business $business): array
    {
        $products = $business->butcherProducts()
            ->with('cutType')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $activeRules = $business->butcherPriceRules()
            ->with(['product', 'outlet'])
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now()->toDateString());
            })
            ->latest('id')
            ->limit(10)
            ->get();

        $avgMargin = $products->isNotEmpty()
            ? round((float) $products->avg('margin_pct'), 2)
            : 0.0;

        return [
            'products_total' => $business->butcherProducts()->count(),
            'products_active' => $products->count(),
            'avg_margin_pct' => $avgMargin,
            'low_margin_count' => $products->filter(fn (ButcherProduct $p) => $p->marginHealth() === 'low')->count(),
            'negative_margin_count' => $products->filter(fn (ButcherProduct $p) => $p->marginHealth() === 'negative')->count(),
            'active_promotions' => $business->butcherPriceRules()
                ->where('is_active', true)
                ->whereNotNull('valid_until')
                ->whereDate('valid_until', '>=', now()->toDateString())
                ->count(),
            'products' => $products,
            'recent_price_rules' => $activeRules,
        ];
    }

    private function recalculateMargin(ButcherProduct $product): void
    {
        $price = (float) $product->default_price;
        $cost = (float) $product->avg_cost_per_kg;

        $product->margin_pct = $price > 0
            ? round((($price - $cost) / $price) * 100, 2)
            : 0.0;

        $product->save();
    }

    /**
     * @param  Collection<int, ButcherPriceRule>  $rules
     */
    private function pickBestPriceRule(Collection $rules, ?int $outletId, ?string $tier): ?ButcherPriceRule
    {
        $candidates = $rules->filter(function (ButcherPriceRule $rule) use ($outletId, $tier) {
            $outletMatch = $rule->outlet_id === null || ($outletId !== null && (int) $rule->outlet_id === $outletId);
            $tierMatch = $rule->customer_tier === null || ($tier !== null && $rule->customer_tier === $tier);

            return $outletMatch && $tierMatch;
        });

        if ($candidates->isEmpty()) {
            return null;
        }

        $priority = [
            ['outlet' => true, 'tier' => true, 'score' => 4],
            ['outlet' => true, 'tier' => false, 'score' => 3],
            ['outlet' => false, 'tier' => true, 'score' => 2],
            ['outlet' => false, 'tier' => false, 'score' => 1],
        ];

        return $candidates
            ->sortByDesc(function (ButcherPriceRule $rule) use ($outletId, $tier, $priority) {
                foreach ($priority as $level) {
                    $outletOk = $level['outlet']
                        ? ($rule->outlet_id !== null && $outletId !== null && (int) $rule->outlet_id === $outletId)
                        : ($rule->outlet_id === null);
                    $tierOk = $level['tier']
                        ? ($rule->customer_tier !== null && $tier !== null && $rule->customer_tier === $tier)
                        : ($rule->customer_tier === null);

                    if ($outletOk && $tierOk) {
                        return $level['score'];
                    }
                }

                return 0;
            })
            ->first();
    }
}
