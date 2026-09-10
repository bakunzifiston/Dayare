<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherCutOutput;
use App\Models\ButcherCuttingSession;
use App\Models\ButcherPriceRule;
use App\Models\ButcherProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ButcherCatalogService
{
    public function createProduct(Business $business, array $data): ButcherProduct
    {
        $wantsActive = (bool) ($data['is_active'] ?? false);

        return DB::transaction(function () use ($business, $data, $wantsActive) {
            $product = $business->butcherProducts()->create([
                'cut_type_id' => $data['cut_type_id'] ?? null,
                'name' => (string) $data['name'],
                'meat_type' => (string) $data['meat_type'],
                'unit' => (string) ($data['unit'] ?? ButcherProduct::UNIT_PER_KG),
                'default_price' => (float) $data['default_price'],
                'avg_cost_per_kg' => (float) ($data['avg_cost_per_kg'] ?? 0),
                'is_active' => false,
            ]);

            if ($product->cut_type_id) {
                $this->recalculateAvgCost($product);
            } else {
                $this->recalculateMargin($product);
            }

            if ($wantsActive) {
                $this->assertCanActivate($product->fresh());
                $product->update(['is_active' => true]);
            }

            return $product->fresh(['cutType', 'priceRules']);
        });
    }

    public function updateProduct(ButcherProduct $product, array $data): void
    {
        DB::transaction(function () use ($product, $data) {
            $cutTypeChanged = array_key_exists('cut_type_id', $data)
                && (int) ($data['cut_type_id'] ?? 0) !== (int) $product->cut_type_id;

            $wantsActive = array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : (bool) $product->is_active;

            $product->fill([
                'cut_type_id' => array_key_exists('cut_type_id', $data) ? $data['cut_type_id'] : $product->cut_type_id,
                'name' => $data['name'] ?? $product->name,
                'meat_type' => $data['meat_type'] ?? $product->meat_type,
                'unit' => $data['unit'] ?? $product->unit,
                'default_price' => $data['default_price'] ?? $product->default_price,
                'is_active' => false,
            ]);
            $product->save();

            if ($cutTypeChanged && $product->cut_type_id) {
                $this->recalculateAvgCost($product);
            } else {
                $this->recalculateMargin($product);
            }

            if ($wantsActive) {
                $this->assertCanActivate($product->fresh());
                $product->update(['is_active' => true]);
            }
        });
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
        return DB::transaction(function () use ($business, $data) {
            $rule = $business->butcherPriceRules()->create([
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

            $this->syncDefaultPriceFromRetailRule($rule);

            return $rule->fresh(['product', 'outlet']);
        });
    }

    public function updatePriceRule(ButcherPriceRule $rule, array $data): ButcherPriceRule
    {
        return DB::transaction(function () use ($rule, $data) {
            $product = $rule->product;
            $becomingInactive = array_key_exists('is_active', $data)
                && ! (bool) $data['is_active']
                && $rule->is_active;

            $rule->fill([
                'outlet_id' => array_key_exists('outlet_id', $data) ? $data['outlet_id'] : $rule->outlet_id,
                'customer_tier' => array_key_exists('customer_tier', $data) ? $data['customer_tier'] : $rule->customer_tier,
                'price' => $data['price'] ?? $rule->price,
                'valid_from' => isset($data['valid_from'])
                    ? Carbon::parse($data['valid_from'])->toDateString()
                    : $rule->valid_from,
                'valid_until' => array_key_exists('valid_until', $data)
                    ? (isset($data['valid_until']) && $data['valid_until'] !== ''
                        ? Carbon::parse($data['valid_until'])->toDateString()
                        : null)
                    : $rule->valid_until,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $rule->is_active,
            ]);
            $rule->save();

            if ($becomingInactive
                && $product
                && $product->is_active
                && ! $this->hasActiveRetailPriceRule($product->fresh())) {
                throw ValidationException::withMessages([
                    'is_active' => [__('Cannot deactivate the last retail price rule while the product is active.')],
                ]);
            }

            $this->syncDefaultPriceFromRetailRule($rule->fresh());

            return $rule->fresh(['product', 'outlet']);
        });
    }

    public function hasActiveRetailPriceRule(ButcherProduct $product): bool
    {
        $today = now()->toDateString();

        return $product->priceRules()
            ->where('is_active', true)
            ->where('customer_tier', ButcherPriceRule::TIER_RETAIL)
            ->whereDate('valid_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', $today);
            })
            ->exists();
    }

    public function assertCanActivate(ButcherProduct $product): void
    {
        if (! $this->hasActiveRetailPriceRule($product)) {
            throw ValidationException::withMessages([
                'is_active' => [__('Add an active retail price rule before activating this product for POS.')],
            ]);
        }
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

    private function syncDefaultPriceFromRetailRule(ButcherPriceRule $rule): void
    {
        if ($rule->customer_tier !== ButcherPriceRule::TIER_RETAIL || ! $rule->is_active) {
            return;
        }

        $product = $rule->product;
        if ($product === null || $rule->outlet_id !== null) {
            return;
        }

        $product->update(['default_price' => $rule->price]);
        $this->recalculateMargin($product->fresh());
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
