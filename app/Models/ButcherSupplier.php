<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ButcherSupplier extends Model
{
    public const TYPE_ABATTOIR = 'abattoir';

    public const TYPE_FARM = 'farm';

    public const TYPE_MARKET = 'market';

    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_OTHER = 'other';

    /** @var list<string> */
    public const SUPPLIER_TYPES = [
        self::TYPE_ABATTOIR,
        self::TYPE_FARM,
        self::TYPE_MARKET,
        self::TYPE_INDIVIDUAL,
        self::TYPE_OTHER,
    ];

    protected $fillable = [
        'business_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'supplier_type',
        'district',
        'sector',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(ButcherPurchaseOrder::class, 'supplier_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ButcherDelivery::class, 'supplier_id');
    }
}
