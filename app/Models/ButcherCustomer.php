<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ButcherCustomer extends Model
{
    public const TIER_RETAIL = 'retail';

    public const TIER_WHOLESALE = 'wholesale';

    public const TIER_LOYALTY = 'loyalty';

    /** @var list<string> */
    public const TIERS = [
        self::TIER_RETAIL,
        self::TIER_WHOLESALE,
        self::TIER_LOYALTY,
    ];

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'tier',
        'credit_limit',
        'outstanding_balance',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(ButcherSale::class, 'customer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ButcherOrder::class, 'customer_id');
    }

    public function availableCredit(): float
    {
        return max((float) $this->credit_limit - (float) $this->outstanding_balance, 0);
    }
}
