<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancePayment extends Model
{
    public const METHOD_CASH = 'cash';

    public const METHOD_MOMO = 'momo';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    /** @var list<string> */
    public const METHODS = [
        self::METHOD_CASH,
        self::METHOD_MOMO,
        self::METHOD_BANK_TRANSFER,
    ];

    public const STATE_PAID = 'paid';

    public const STATE_UNPAID = 'unpaid';

    public const STATE_PENDING = 'pending';

    /** @var list<string> */
    public const STATES = [
        self::STATE_PAID,
        self::STATE_UNPAID,
        self::STATE_PENDING,
    ];

    protected $fillable = [
        'business_id',
        'facility_id',
        'payable_type',
        'payable_id',
        'amount',
        'method',
        'reference',
        'paid_at',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public static function methodLabel(string $method): string
    {
        return match ($method) {
            self::METHOD_CASH => __('Cash'),
            self::METHOD_MOMO => __('Mobile Money'),
            self::METHOD_BANK_TRANSFER => __('Bank Transfer'),
            default => $method,
        };
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
