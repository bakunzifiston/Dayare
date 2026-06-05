<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeatExportDocument extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ISSUED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
    ];

    protected $fillable = [
        'delivery_confirmation_id',
        'document_type',
        'document_number',
        'issuing_authority',
        'issued_date',
        'expiry_date',
        'status',
        'notes',
        'file_path',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function deliveryConfirmation(): BelongsTo
    {
        return $this->belongsTo(DeliveryConfirmation::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isComplete(): bool
    {
        return $this->status === self::STATUS_ISSUED && ! $this->isExpired();
    }
}
