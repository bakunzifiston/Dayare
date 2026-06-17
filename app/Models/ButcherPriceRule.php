<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherPriceRule extends Model
{
    public const TIER_RETAIL = 'retail';

    public const TIER_WHOLESALE = 'wholesale';

    public const TIER_LOYALTY = 'loyalty';

    /** @var list<string> */
    public const CUSTOMER_TIERS = [
        self::TIER_RETAIL,
        self::TIER_WHOLESALE,
        self::TIER_LOYALTY,
    ];

    protected $fillable = [
        'business_id',
        'product_id',
        'outlet_id',
        'customer_tier',
        'price',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ButcherProduct::class, 'product_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(ButcherOutlet::class, 'outlet_id');
    }

    public function isCurrentlyValid(?\Carbon\Carbon $on = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $date = ($on ?? now())->toDateString();

        if ($this->valid_from->toDateString() > $date) {
            return false;
        }

        if ($this->valid_until !== null && $this->valid_until->toDateString() < $date) {
            return false;
        }

        return true;
    }

    public function labelDescription(): string
    {
        $parts = [];

        if ($this->outlet) {
            $parts[] = $this->outlet->name;
        } else {
            $parts[] = __('All outlets');
        }

        if ($this->customer_tier) {
            $parts[] = ucfirst($this->customer_tier);
        } else {
            $parts[] = __('All tiers');
        }

        return implode(' · ', $parts);
    }
}
