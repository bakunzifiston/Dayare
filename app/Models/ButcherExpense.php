<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ButcherExpense extends Model
{
    public const CATEGORY_RENT = 'rent';

    public const CATEGORY_UTILITIES = 'utilities';

    public const CATEGORY_WAGES = 'wages';

    public const CATEGORY_TRANSPORT = 'transport';

    public const CATEGORY_MAINTENANCE = 'maintenance';

    public const CATEGORY_SUPPLIES = 'supplies';

    public const CATEGORY_OTHER = 'other';

    /** @var list<string> */
    public const CATEGORIES = [
        self::CATEGORY_RENT,
        self::CATEGORY_UTILITIES,
        self::CATEGORY_WAGES,
        self::CATEGORY_TRANSPORT,
        self::CATEGORY_MAINTENANCE,
        self::CATEGORY_SUPPLIES,
        self::CATEGORY_OTHER,
    ];

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_MOMO = 'momo';

    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    /** @var list<string> */
    public const PAYMENT_METHODS = [
        self::PAYMENT_CASH,
        self::PAYMENT_MOMO,
        self::PAYMENT_BANK_TRANSFER,
    ];

    protected $fillable = [
        'business_id',
        'outlet_id',
        'category',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'receipt_path',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
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

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function receiptUrl(): ?string
    {
        if ($this->receipt_path === null || $this->receipt_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->receipt_path);
    }
}
