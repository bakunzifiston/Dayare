<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Animal extends Model
{
    use SoftDeletes;

    public const HEALTH_HEALTHY = 'healthy';

    public const HEALTH_SICK = 'sick';

    public const HEALTH_INJURED = 'injured';

    public const HEALTH_PREGNANT = 'pregnant';

    public const HEALTH_UNDER_TREATMENT = 'under_treatment';

    public const HEALTH_QUARANTINED = 'quarantined';

    /** @var list<string> */
    public const HEALTH_STATUSES = [
        self::HEALTH_HEALTHY,
        self::HEALTH_SICK,
        self::HEALTH_INJURED,
        self::HEALTH_PREGNANT,
        self::HEALTH_UNDER_TREATMENT,
        self::HEALTH_QUARANTINED,
    ];

    public const PRODUCTION_GROWING = 'growing';

    public const PRODUCTION_LACTATING = 'lactating';

    public const PRODUCTION_BREEDING = 'breeding';

    public const PRODUCTION_READY_FOR_SALE = 'ready_for_sale';

    public const PRODUCTION_DRY = 'dry';

    /** @var list<string> */
    public const PRODUCTION_STATUSES = [
        self::PRODUCTION_GROWING,
        self::PRODUCTION_LACTATING,
        self::PRODUCTION_BREEDING,
        self::PRODUCTION_READY_FOR_SALE,
        self::PRODUCTION_DRY,
    ];

    public const LIFECYCLE_ACTIVE = 'active';

    public const LIFECYCLE_SOLD = 'sold';

    public const LIFECYCLE_DEAD = 'dead';

    public const LIFECYCLE_TRANSFERRED = 'transferred';

    /** @var list<string> */
    public const LIFECYCLE_STATUSES = [
        self::LIFECYCLE_ACTIVE,
        self::LIFECYCLE_SOLD,
        self::LIFECYCLE_DEAD,
        self::LIFECYCLE_TRANSFERRED,
    ];

    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    public const GENDER_UNKNOWN = 'unknown';

    /** @var list<string> */
    public const GENDERS = [
        self::GENDER_MALE,
        self::GENDER_FEMALE,
        self::GENDER_UNKNOWN,
    ];

    protected $fillable = [
        'livestock_id',
        'animal_code',
        'tag_number',
        'qr_code',
        'public_verification_token',
        'animal_name',
        'gender',
        'birth_date',
        'age',
        'weight',
        'color_markings',
        'acquisition_type',
        'acquisition_date',
        'source',
        'mother_tag',
        'father_tag',
        'health_status',
        'production_status',
        'lifecycle_status',
        'current_condition',
        'photo_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'acquisition_date' => 'date',
            'age' => 'float',
            'weight' => 'float',
        ];
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function diseaseRecords(): HasMany
    {
        return $this->hasMany(DiseaseRecord::class);
    }

    public function veterinaryVisits(): HasMany
    {
        return $this->hasMany(VeterinaryVisit::class);
    }

    public function mortalityRecord(): HasOne
    {
        return $this->hasOne(MortalityRecord::class);
    }

    public function feedingRecords(): HasMany
    {
        return $this->hasMany(FeedingRecord::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(AnimalCertificate::class);
    }

    public function ownershipTransfers(): HasMany
    {
        return $this->hasMany(AnimalOwnershipTransfer::class);
    }

    public function saleAnimals(): HasMany
    {
        return $this->hasMany(SaleAnimal::class);
    }

    public function movementPermitAnimals(): HasMany
    {
        return $this->hasMany(MovementPermitAnimal::class);
    }

    public function publicVerificationUrl(): ?string
    {
        if ($this->public_verification_token === null || $this->public_verification_token === '') {
            return null;
        }

        return route('animal.verify', ['token' => $this->public_verification_token]);
    }

    public function farm(): ?Farm
    {
        return $this->livestock?->farm;
    }

    public function photoUrl(): ?string
    {
        if ($this->photo_path === null || $this->photo_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    public function displayIdentifier(): string
    {
        $tag = trim((string) $this->tag_number);

        return $tag !== '' ? $tag : $this->animal_code;
    }

    public function selectionLabel(): string
    {
        $parts = [$this->displayIdentifier()];

        $name = trim((string) $this->animal_name);
        if ($name !== '') {
            $parts[] = $name;
        }

        $farmName = trim((string) ($this->livestock?->farm?->name ?? ''));
        if ($farmName !== '') {
            $parts[] = $farmName;
        } elseif (filled($this->tag_number) && $this->animal_code !== $this->tag_number) {
            $parts[] = $this->animal_code;
        }

        return implode(' · ', array_values(array_unique(array_filter($parts))));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeOrderedForSelection($query)
    {
        return $query
            ->orderByRaw('CASE WHEN COALESCE(tag_number, "") = "" THEN 1 ELSE 0 END')
            ->orderBy('tag_number')
            ->orderBy('animal_code');
    }
}
