<?php

namespace App\Models;

use App\Models\Concerns\DefinesButcherMeatTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ButcherProduct extends Model
{
    use DefinesButcherMeatTypes;

    public const UNIT_PER_KG = 'per_kg';

    public const UNIT_PER_PIECE = 'per_piece';

    public const UNIT_PER_PACK = 'per_pack';

    /** @var list<string> */
    public const UNITS = [
        self::UNIT_PER_KG,
        self::UNIT_PER_PIECE,
        self::UNIT_PER_PACK,
    ];

    protected $fillable = [
        'business_id',
        'cut_type_id',
        'name',
        'meat_type',
        'unit',
        'default_price',
        'avg_cost_per_kg',
        'margin_pct',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'avg_cost_per_kg' => 'decimal:2',
            'margin_pct' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function cutType(): BelongsTo
    {
        return $this->belongsTo(ButcherCutType::class, 'cut_type_id');
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(ButcherPriceRule::class, 'product_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(ButcherSaleItem::class, 'product_id');
    }

    public function marginHealth(): string
    {
        $margin = (float) $this->margin_pct;

        if ($margin < 0) {
            return 'negative';
        }

        if ($margin < 15) {
            return 'low';
        }

        return 'healthy';
    }
}
