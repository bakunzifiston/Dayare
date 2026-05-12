<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    public const STATUS_PAID = 'paid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REFUNDED = 'refunded';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PAID,
        self::STATUS_PARTIAL,
        self::STATUS_PENDING,
        self::STATUS_REFUNDED,
    ];

    protected $fillable = [
        'sale_id',
        'payment_reference',
        'payment_date',
        'payment_method',
        'amount_paid',
        'remaining_balance',
        'transaction_reference',
        'payment_status',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount_paid' => 'float',
            'remaining_balance' => 'float',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
