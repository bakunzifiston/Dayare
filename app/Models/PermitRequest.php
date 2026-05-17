<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermitRequest extends Model
{
    use SoftDeletes;

    public const PURPOSE_SALE = 'sale';

    public const PURPOSE_BREEDING = 'breeding';

    public const PURPOSE_SLAUGHTER = 'slaughter';

    public const PURPOSE_VACCINATION = 'vaccination';

    public const PURPOSE_EXHIBITION = 'exhibition';

    public const PURPOSE_TRANSFER = 'transfer';

    /** @var list<string> */
    public const PURPOSES = [
        self::PURPOSE_SALE,
        self::PURPOSE_BREEDING,
        self::PURPOSE_SLAUGHTER,
        self::PURPOSE_VACCINATION,
        self::PURPOSE_EXHIBITION,
        self::PURPOSE_TRANSFER,
    ];

    public const DESTINATION_FARM = 'farm';

    public const DESTINATION_MARKET = 'market';

    public const DESTINATION_ABATTOIR = 'abattoir';

    public const DESTINATION_BORDER = 'border';

    public const DESTINATION_COOPERATIVE = 'cooperative';

    /** @var list<string> */
    public const DESTINATION_TYPES = [
        self::DESTINATION_FARM,
        self::DESTINATION_MARKET,
        self::DESTINATION_ABATTOIR,
        self::DESTINATION_BORDER,
        self::DESTINATION_COOPERATIVE,
    ];

    public const TRANSPORT_VEHICLE = 'vehicle';

    public const TRANSPORT_WALKING = 'walking';

    public const TRANSPORT_MOTORCYCLE = 'motorcycle';

    public const TRANSPORT_TRUCK = 'truck';

    /** @var list<string> */
    public const TRANSPORT_METHODS = [
        self::TRANSPORT_VEHICLE,
        self::TRANSPORT_WALKING,
        self::TRANSPORT_MOTORCYCLE,
        self::TRANSPORT_TRUCK,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PERMIT_ISSUED = 'permit_issued';

    public const STATUS_COMPLETED = 'completed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_PERMIT_ISSUED,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'request_number',
        'request_date',
        'applicant_id',
        'farm_id',
        'farmer_id',
        'movement_purpose',
        'destination_type',
        'destination_name',
        'destination_district',
        'destination_sector',
        'destination_cell',
        'destination_village',
        'transport_method',
        'vehicle_plate_number',
        'proposed_departure_date',
        'expected_arrival_date',
        'remarks',
        'status',
        'reviewed_by',
        'review_date',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'proposed_departure_date' => 'date',
            'expected_arrival_date' => 'date',
            'review_date' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'farmer_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(PermitRequestAnimal::class);
    }

    public function permit(): HasOne
    {
        return $this->hasOne(MovementPermit::class);
    }

    public function destinationLabel(): string
    {
        $parts = array_filter([
            $this->destination_name,
            $this->destination_village,
            $this->destination_cell,
            $this->destination_sector,
            $this->destination_district,
        ]);

        return $parts !== [] ? implode(', ', $parts) : ucwords(str_replace('_', ' ', (string) $this->destination_type));
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function canIssuePermit(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PERMIT_ISSUED], true)
            && $this->permit === null;
    }
}
