<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Certificate – legal proof of inspection.
 * Certificate belongs to: Batch, Inspector, Facility.
 * Batch (1) → One Certificate. Certificate allowed only if post-mortem approved_quantity > 0.
 */
class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'inspector_id',
        'facility_id',
        'certificate_number',
        'issued_at',
        'expiry_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
        self::STATUS_REVOKED,
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Inspector::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /** Certificate (1) → One QR */
    public function certificateQr(): HasOne
    {
        return $this->hasOne(CertificateQr::class);
    }

    public function transportTrips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransportTrip::class);
    }

    /** Certificate (1) → Required before storage; can have many warehouse storages over time */
    public function warehouseStorages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WarehouseStorage::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
