<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ButcherSale extends Model
{
    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_MOMO = 'momo';

    public const PAYMENT_CARD = 'card';

    public const PAYMENT_CREDIT = 'credit';

    public const PAYMENT_SPLIT = 'split';

    /** @var list<string> */
    public const PAYMENT_METHODS = [
        self::PAYMENT_CASH,
        self::PAYMENT_MOMO,
        self::PAYMENT_CARD,
        self::PAYMENT_CREDIT,
        self::PAYMENT_SPLIT,
    ];

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_PENDING,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'business_id',
        'outlet_id',
        'sale_number',
        'customer_id',
        'sale_date',
        'subtotal',
        'discount_amount',
        'total_amount',
        'payment_method',
        'amount_paid',
        'change_given',
        'status',
        'sold_by',
        'receipt_path',
        'invoice_path',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_given' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(ButcherOutlet::class, 'outlet_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ButcherCustomer::class, 'customer_id');
    }

    public function soldByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ButcherSaleItem::class, 'sale_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ButcherSalePayment::class, 'sale_id');
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_PENDING], true);
    }
}
