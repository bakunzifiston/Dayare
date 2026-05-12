<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    public const TYPE_INDIVIDUAL = 'individual_animal_sale';

    public const TYPE_GROUP = 'livestock_group_sale';

    public const TYPE_MARKET = 'market_sale';

    public const TYPE_SLAUGHTER = 'slaughter_sale';

    public const TYPE_BREEDING = 'breeding_sale';

    public const TYPE_EXPORT = 'export_sale';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_INDIVIDUAL,
        self::TYPE_GROUP,
        self::TYPE_MARKET,
        self::TYPE_SLAUGHTER,
        self::TYPE_BREEDING,
        self::TYPE_EXPORT,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
    ];

    /** @var list<string> */
    public const OPEN_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
    ];

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_OVERDUE = 'overdue';

    public const PAYMENT_REFUNDED = 'refunded';

    /** @var list<string> */
    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING,
        self::PAYMENT_PARTIAL,
        self::PAYMENT_PAID,
        self::PAYMENT_OVERDUE,
        self::PAYMENT_REFUNDED,
    ];

    public const METHOD_CASH = 'cash';

    public const METHOD_MOBILE_MONEY = 'mobile_money';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CREDIT = 'credit';

    /** @var list<string> */
    public const PAYMENT_METHODS = [
        self::METHOD_CASH,
        self::METHOD_MOBILE_MONEY,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_CREDIT,
    ];

    public const CERT_UNVERIFIED = 'unverified';

    public const CERT_PARTIAL = 'partial';

    public const CERT_VERIFIED = 'verified';

    /** @var list<string> */
    public const CERTIFICATE_STATUSES = [
        self::CERT_UNVERIFIED,
        self::CERT_PARTIAL,
        self::CERT_VERIFIED,
    ];

    protected $fillable = [
        'farm_id',
        'sale_number',
        'buyer_id',
        'sale_type',
        'sale_date',
        'sale_status',
        'payment_status',
        'payment_method',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'currency',
        'delivery_method',
        'destination',
        'movement_permit_id',
        'certificate_status',
        'approved_by',
        'notes',
        'attachment_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'subtotal_amount' => 'float',
            'discount_amount' => 'float',
            'tax_amount' => 'float',
            'total_amount' => 'float',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function movementPermit(): BelongsTo
    {
        return $this->belongsTo(MovementPermit::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function saleAnimals(): HasMany
    {
        return $this->hasMany(SaleAnimal::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SaleDocument::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SaleLog::class);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount_paid');
    }

    public function remainingBalance(): float
    {
        return max(0, (float) $this->total_amount - $this->amountPaid());
    }

    public function isEditable(): bool
    {
        return in_array($this->sale_status, [self::STATUS_DRAFT, self::STATUS_PENDING], true);
    }
}
