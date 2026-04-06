<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Batch – group of carcasses from slaughter.
 * SlaughterExecution (1) → Many Batches.
 * Batch belongs to: SlaughterExecution, Inspector.
 * Batch (1) → One Post-Mortem Inspection.
 */
class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'slaughter_execution_id',
        'inspector_id',
        'species',
        'quantity',
        'batch_code',
        'status',
        'cold_chain_status',
    ];

    /** Cold room / temperature compliance (separate from inspection status). */
    public const COLD_CHAIN_OK = 'ok';

    public const COLD_CHAIN_AT_RISK = 'at_risk';

    public const COLD_CHAIN_COMPROMISED = 'compromised';

    public const COLD_CHAIN_STATUSES = [
        self::COLD_CHAIN_OK,
        self::COLD_CHAIN_AT_RISK,
        self::COLD_CHAIN_COMPROMISED,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const SPECIES_CATTLE = 'Cattle';

    public const SPECIES_GOAT = 'Goat';

    public const SPECIES_SHEEP = 'Sheep';

    public const SPECIES_PIG = 'Pig';

    public const SPECIES_OTHER = 'Other';

    public const SPECIES_OPTIONS = [
        self::SPECIES_CATTLE,
        self::SPECIES_GOAT,
        self::SPECIES_SHEEP,
        self::SPECIES_PIG,
        self::SPECIES_OTHER,
    ];

    protected static function booted(): void
    {
        static::creating(function (Batch $batch) {
            if (empty($batch->batch_code)) {
                $batch->batch_code = 'BAT-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));
            }
        });
    }

    /** Batch belongs to SlaughterExecution */
    public function slaughterExecution(): BelongsTo
    {
        return $this->belongsTo(SlaughterExecution::class);
    }

    /** Batch belongs to Inspector */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Inspector::class);
    }

    /** Batch (1) → One Post-Mortem Inspection */
    public function postMortemInspection(): HasOne
    {
        return $this->hasOne(PostMortemInspection::class);
    }

    /** Batch (1) → One Certificate (allowed only if post-mortem approved_quantity > 0) */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    /** Batch (1) → Can have One WarehouseStorage */
    public function warehouseStorage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WarehouseStorage::class);
    }

    public function canIssueCertificate(): bool
    {
        $pm = $this->postMortemInspection;

        return $pm && $pm->approved_quantity > 0;
    }

    public function transportTrips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransportTrip::class);
    }
}
