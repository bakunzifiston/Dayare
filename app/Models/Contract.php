<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    public const TYPE_SUPPLY_AGREEMENT = 'supply_agreement';
    public const TYPE_SLAUGHTER_AGREEMENT = 'slaughter_agreement';
    public const TYPE_SALE_AGREEMENT = 'sale_agreement';
    public const TYPE_SERVICE_AGREEMENT = 'service_agreement';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_SUPPLY_AGREEMENT => 'Supply agreement',
        self::TYPE_SLAUGHTER_AGREEMENT => 'Slaughter agreement',
        self::TYPE_SALE_AGREEMENT => 'Sale agreement',
        self::TYPE_SERVICE_AGREEMENT => 'Service agreement',
        self::TYPE_OTHER => 'Other',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TERMINATED = 'terminated';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_EXPIRED => 'Expired',
        self::STATUS_TERMINATED => 'Terminated',
    ];

    protected $fillable = [
        'business_id',
        'supplier_id',
        'facility_id',
        'contract_number',
        'title',
        'type',
        'start_date',
        'end_date',
        'status',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /** Display name for the counterparty (supplier or facility). */
    public function getCounterpartyNameAttribute(): string
    {
        if ($this->supplier_id && $this->relationLoaded('supplier') && $this->supplier) {
            return trim(($this->supplier->first_name ?? '') . ' ' . ($this->supplier->last_name ?? '')) ?: ($this->supplier->name ?? 'Supplier #' . $this->supplier_id);
        }
        if ($this->facility_id && $this->relationLoaded('facility') && $this->facility) {
            return $this->facility->facility_name ?? 'Facility #' . $this->facility_id;
        }
        return '—';
    }

    public function isExpired(): bool
    {
        if (! $this->end_date) {
            return false;
        }
        return $this->end_date->isPast();
    }
}
