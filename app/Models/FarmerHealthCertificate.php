<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerHealthCertificate extends Model
{
    public const TYPE_HEALTH = 'health_certificate';

    public const TYPE_VACCINATION = 'vaccination_certificate';

    public const TYPE_BREED = 'breed_certificate';

    public const TYPE_MOVEMENT_PERMIT = 'movement_permit';

    public const TYPE_SALE_OWNERSHIP = 'sale_ownership_record';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_HEALTH,
        self::TYPE_VACCINATION,
        self::TYPE_BREED,
        self::TYPE_MOVEMENT_PERMIT,
        self::TYPE_SALE_OWNERSHIP,
    ];

    public const STATUS_VALID = 'valid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_VALID,
        self::STATUS_EXPIRED,
        self::STATUS_REVOKED,
    ];

    protected $fillable = [
        'certificate_number',
        'farmer_id',
        'farm_id',
        'livestock_id',
        'batch_reference',
        'source_health_record_id',
        'certificate_type',
        'issued_by',
        'issue_date',
        'expiry_date',
        'status',
        'file_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'farmer_id');
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }

    public function sourceHealthRecord(): BelongsTo
    {
        return $this->belongsTo(AnimalHealthRecord::class, 'source_health_record_id');
    }

    public function isValidOn(\Carbon\CarbonInterface $date): bool
    {
        if ($this->status !== self::STATUS_VALID) {
            return false;
        }

        if ($this->issue_date === null || $this->issue_date->gt($date)) {
            return false;
        }

        return $this->expiry_date === null || $this->expiry_date->gte($date);
    }
}

