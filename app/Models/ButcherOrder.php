<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ButcherOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_READY = 'ready';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_READY,
        self::STATUS_FULFILLED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'business_id',
        'customer_id',
        'order_number',
        'order_date',
        'delivery_date',
        'total_amount',
        'deposit_paid',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'delivery_date' => 'date',
            'total_amount' => 'decimal:2',
            'deposit_paid' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ButcherCustomer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ButcherOrderItem::class, 'order_id');
    }
}
