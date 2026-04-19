<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsInvoice extends Model
{
    protected $table = 'logistics_invoices';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PARTIALLY_PAID = 'partially_paid';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_OVERDUE = 'overdue';

    public const PAYMENT_CANCELLED = 'cancelled';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING,
        self::PAYMENT_PARTIALLY_PAID,
        self::PAYMENT_PAID,
        self::PAYMENT_OVERDUE,
        self::PAYMENT_CANCELLED,
    ];

    protected $fillable = [
        'trip_id',
        'order_id',
        'client_id',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'issued_at',
        'due_date',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_date' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LogisticsInvoice $invoice): void {
            if ($invoice->invoice_number !== null && $invoice->invoice_number !== '') {
                return;
            }
            do {
                $invoice->invoice_number = 'INV-'.strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            } while (static::query()->where('invoice_number', $invoice->invoice_number)->exists());
        });
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(LogisticsTrip::class, 'trip_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LogisticsOrder::class, 'order_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LogisticsInvoiceItem::class, 'invoice_id');
    }
}
