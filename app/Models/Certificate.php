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
        'slaughterhouse_display_name',
        'pdf_details',
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
            'pdf_details' => 'array',
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

    /** Certificate is valid for use (active and not expired). */
    public function isCompliant(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        return ! $this->expiry_date || ! $this->expiry_date->isPast();
    }

    /** Scope: only certificates that are compliant (active and not expired). */
    public function scopeCompliant($query)
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->startOfDay()));
    }

    // --- Section 1 ---

    protected static function booted(): void
    {
        static::saving(function (Certificate $cert) {
            if ($cert->status !== self::STATUS_REVOKED
                && $cert->expiry_date
                && $cert->expiry_date->isPast()
            ) {
                $cert->status = self::STATUS_EXPIRED;
            }
        });
    }

    /**
     * Whether the certificate expiry date is in the past.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Whether the certificate has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    /**
     * True when not revoked and expiry date is absent or still in the future.
     * Prefer this over the stored status field for compliance checks.
     */
    public function isCurrentlyValid(): bool
    {
        if ($this->isRevoked()) {
            return false;
        }
        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Tailwind badge classes for the derived certificate status label.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->isRevoked()) {
            return 'bg-red-100 text-red-800';
        }
        if ($this->isExpired()) {
            return 'bg-yellow-100 text-yellow-800';
        }

        return 'bg-green-100 text-green-800';
    }

    /**
     * Human-readable status derived from revocation and expiry date.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->isRevoked()) {
            return 'Revoked';
        }
        if ($this->isExpired()) {
            return 'Expired';
        }

        return 'Active';
    }
}
