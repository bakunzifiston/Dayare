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

    public const STATUS_ISSUED = 'issued';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_USED = 'used';

    public const STATUS_REVOKED = 'revoked';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_ISSUED,
        self::STATUS_ACTIVE,
        self::STATUS_USED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
        self::STATUS_REVOKED,
    ];

    /** @var list<string> */
    public const ACTIVE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_ISSUED,
        self::STATUS_ACTIVE,
    ];

    /** @var list<string> */
    public const VALID_FOR_MOVEMENT_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_ISSUED,
        self::STATUS_ACTIVE,
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
        'permit_request_id',
        'permit_number',
        'permit_type',
        'movement_reason',
        'livestock_type',
        'owner_name',
        'owner_national_id',
        'owner_identification_number',
        'owner_phone',
        'owner_address',
        'farmer_id',
        'source_farm_id',
        'origin_location',
        'source_district',
        'source_sector',
        'source_cell',
        'source_village',
        'destination_location',
        'destination_district',
        'destination_sector',
        'destination_cell',
        'destination_village',
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
        'issuing_authority',
        'permit_status',
        'veterinary_status',
        'movement_status',
        'qr_code',
        'qr_code_path',
        'verification_token',
        'verification_code',
        'approved_by',
        'notes',
        'attachment_path',
        'pdf_path',
        'file_path',
        'created_by',
        'imported_from_pdf',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'departure_date' => 'date',
            'expected_arrival_date' => 'date',
            'imported_from_pdf' => 'boolean',
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

    public function permitRequest(): BelongsTo
    {
        return $this->belongsTo(PermitRequest::class);
    }

    public function movementHistories(): HasMany
    {
        return $this->hasMany(MovementHistory::class);
    }

    public function verificationUrl(): ?string
    {
        if (! $this->verification_token) {
            return null;
        }

        return route('movement.verify', ['token' => $this->verification_token]);
    }

    public function publicVerificationUrl(): string
    {
        return route('verify.permit.show', ['identifier' => $this->permit_number]);
    }

    public function ownerIdentification(): ?string
    {
        return $this->owner_identification_number ?: $this->owner_national_id;
    }

    public function sourceLocationLabel(): string
    {
        $parts = array_filter([
            $this->source_village,
            $this->source_cell,
            $this->source_sector,
            $this->source_district,
        ]);

        if ($parts !== []) {
            return implode(', ', $parts);
        }

        return (string) ($this->origin_location ?: $this->sourceFarm?->name ?: '');
    }

    public function destinationLocationLabel(): string
    {
        $parts = array_filter([
            $this->destination_village,
            $this->destination_cell,
            $this->destination_sector,
            $this->destination_district,
        ]);

        if ($parts !== []) {
            return implode(', ', $parts);
        }

        return (string) ($this->destination_location ?: '');
    }

    public function syncExpiryStatus(): void
    {
        if ($this->expiry_date && $this->expiry_date->isPast()
            && ! in_array($this->permit_status, [self::STATUS_EXPIRED, self::STATUS_CANCELLED, self::STATUS_REVOKED], true)) {
            $this->update(['permit_status' => self::STATUS_EXPIRED]);
        }
    }

    public function isValidOn(\Carbon\CarbonInterface $date): bool
    {
        $this->syncExpiryStatus();

        if (in_array($this->permit_status, [self::STATUS_EXPIRED, self::STATUS_CANCELLED, self::STATUS_REVOKED, self::STATUS_REJECTED], true)) {
            return false;
        }

        $start = $this->departure_date ?? $this->issue_date;
        $end = $this->expected_arrival_date ?? $this->expiry_date;

        $statusOk = in_array($this->permit_status, self::VALID_FOR_MOVEMENT_STATUSES, true);
        $vetOk = $this->imported_from_pdf || $this->veterinary_status === self::VET_CLEARED;

        return $start !== null
            && $end !== null
            && $start->lte($date)
            && $end->gte($date)
            && $statusOk
            && $vetOk;
    }

    public function isEditable(): bool
    {
        return in_array($this->permit_status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL], true);
    }
}
