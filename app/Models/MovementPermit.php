<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovementPermit extends Model
{
    use SoftDeletes;

    public const TYPE_FARM_TRANSFER = 'farm_transfer';

    public const TYPE_MARKET_TRANSPORT = 'market_transport';

    public const TYPE_SLAUGHTER_TRANSPORT = 'slaughter_transport';

    public const TYPE_VETERINARY_REFERRAL = 'veterinary_referral';

    public const TYPE_BREEDING_TRANSFER = 'breeding_transfer';

    public const TYPE_QUARANTINE_MOVEMENT = 'quarantine_movement';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_FARM_TRANSFER,
        self::TYPE_MARKET_TRANSPORT,
        self::TYPE_SLAUGHTER_TRANSPORT,
        self::TYPE_VETERINARY_REFERRAL,
        self::TYPE_BREEDING_TRANSFER,
        self::TYPE_QUARANTINE_MOVEMENT,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    /** @var list<string> */
    public const ACTIVE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
    ];

    public const VET_PENDING = 'pending_inspection';

    public const VET_CLEARED = 'cleared';

    public const VET_REJECTED = 'rejected';

    /** @var list<string> */
    public const VETERINARY_STATUSES = [
        self::VET_PENDING,
        self::VET_CLEARED,
        self::VET_REJECTED,
    ];

    public const MOVEMENT_PENDING = 'pending';

    public const MOVEMENT_IN_TRANSIT = 'in_transit';

    public const MOVEMENT_ARRIVED = 'arrived';

    public const MOVEMENT_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const MOVEMENT_STATUSES = [
        self::MOVEMENT_PENDING,
        self::MOVEMENT_IN_TRANSIT,
        self::MOVEMENT_ARRIVED,
        self::MOVEMENT_CANCELLED,
    ];

    protected $fillable = [
        'permit_number',
        'permit_type',
        'movement_reason',
        'farmer_id',
        'source_farm_id',
        'origin_location',
        'destination_location',
        'destination_district_id',
        'destination_sector_id',
        'destination_cell_id',
        'destination_village_id',
        'departure_date',
        'expected_arrival_date',
        'transport_mode',
        'vehicle_plate',
        'driver_name',
        'driver_phone',
        'transporter_name',
        'issue_date',
        'expiry_date',
        'issued_by',
        'permit_status',
        'veterinary_status',
        'movement_status',
        'qr_code',
        'verification_token',
        'approved_by',
        'notes',
        'attachment_path',
        'pdf_path',
        'file_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'departure_date' => 'date',
            'expected_arrival_date' => 'date',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'farmer_id');
    }

    public function sourceFarm(): BelongsTo
    {
        return $this->belongsTo(Farm::class, 'source_farm_id');
    }

    public function farm(): BelongsTo
    {
        return $this->sourceFarm();
    }

    public function destinationDistrict(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'destination_district_id');
    }

    public function destinationSector(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'destination_sector_id');
    }

    public function destinationCell(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'destination_cell_id');
    }

    public function destinationVillage(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'destination_village_id');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(MovementPermitAnimal::class);
    }

    public function transport(): HasOne
    {
        return $this->hasOne(MovementTransport::class);
    }

    public function veterinaryApproval(): HasOne
    {
        return $this->hasOne(MovementVeterinaryApproval::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MovementLog::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function livestockEvents(): HasMany
    {
        return $this->hasMany(LivestockEvent::class);
    }

    public function verificationUrl(): ?string
    {
        if (! $this->verification_token) {
            return null;
        }

        return route('movement.verify', ['token' => $this->verification_token]);
    }

    public function isValidOn(\Carbon\CarbonInterface $date): bool
    {
        if ($this->permit_status === self::STATUS_EXPIRED || $this->permit_status === self::STATUS_CANCELLED) {
            return false;
        }

        $start = $this->departure_date ?? $this->issue_date;
        $end = $this->expected_arrival_date ?? $this->expiry_date;

        return $start !== null
            && $end !== null
            && $start->lte($date)
            && $end->gte($date)
            && $this->permit_status === self::STATUS_APPROVED
            && $this->veterinary_status === self::VET_CLEARED;
    }

    public function isEditable(): bool
    {
        return in_array($this->permit_status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL], true);
    }
}
