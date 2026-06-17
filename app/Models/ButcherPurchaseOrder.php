<?php

namespace App\Models;

use App\Models\Concerns\DefinesButcherMeatTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ButcherPurchaseOrder extends Model
{
    use DefinesButcherMeatTypes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_CONFIRMED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'business_id',
        'supplier_id',
        'po_number',
        'meat_type',
        'requested_weight_kg',
        'requested_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_weight_kg' => 'decimal:3',
            'requested_date' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ButcherSupplier::class, 'supplier_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ButcherDelivery::class, 'purchase_order_id');
    }

    public function latestDelivery(): HasOne
    {
        return $this->hasOne(ButcherDelivery::class, 'purchase_order_id')->latestOfMany();
    }
}
