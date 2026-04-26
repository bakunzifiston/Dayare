<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'business_name',
        'business_name_normalized',
        'registration_number',
        'tax_id',
        'contact_phone',
        'email',
        'status',
        // Ownership info
        'owner_first_name',
        'owner_last_name',
        'owner_dob',
        'owner_gender',
        'owner_pwd_status',
        'owner_name',
        'owner_phone',
        'owner_email',
        'ownership_type',
        'business_size',
        'baseline_revenue',
        'vibe_unique_id',
        'vibe_commencement_date',
        'pathway_status',
        'vibe_comments',
        // Location info
        'address_line_1',
        'address_line_2',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'city',
        'state_region',
        'postal_code',
        'country',
    ];

    public const TYPE_FARMER = 'farmer';

    public const TYPE_PROCESSOR = 'processor';

    public const TYPE_LOGISTICS = 'logistics';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_FARMER,
        self::TYPE_PROCESSOR,
        self::TYPE_LOGISTICS,
    ];

    public const OWNERSHIP_TYPES = ['sole_proprietor', 'partnership', 'company', 'cooperative', 'other'];

    public const OWNERSHIP_TYPES_WITH_MEMBERS = ['partnership', 'cooperative', 'company'];

    public const OWNER_GENDERS = ['male', 'female', 'other'];

    public const OWNER_PWD_STATUSES = ['none', 'physical', 'visual', 'hearing', 'cognitive', 'other'];

    public const BUSINESS_SIZES = ['micro', 'small', 'medium', 'large'];

    /** Annual revenue band (RWF) — stored on `baseline_revenue` instead of a raw amount. */
    public const BASELINE_REVENUE_BRACKET_LT_2M = 'lt_2m';

    public const BASELINE_REVENUE_BRACKET_2M_20M = '2m_20m';

    public const BASELINE_REVENUE_BRACKET_20M_100M = '20m_100m';

    public const BASELINE_REVENUE_BRACKET_GT_101M = 'gt_101m';

    /** @var list<string> */
    public const BASELINE_REVENUE_BRACKETS = [
        self::BASELINE_REVENUE_BRACKET_LT_2M,
        self::BASELINE_REVENUE_BRACKET_2M_20M,
        self::BASELINE_REVENUE_BRACKET_20M_100M,
        self::BASELINE_REVENUE_BRACKET_GT_101M,
    ];

    public const PATHWAY_STATUSES = ['active', 'verification', 'inactive', 'graduated'];

    protected $casts = [
        'owner_dob' => 'date',
        'vibe_commencement_date' => 'date',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $business): void {
            if ($business->business_name !== null) {
                $collapsedWhitespaceName = preg_replace('/\s+/', ' ', trim((string) $business->business_name)) ?? '';
                $business->business_name = $collapsedWhitespaceName;
                $business->business_name_normalized = Str::lower($collapsedWhitespaceName);
            }

            if ($business->registration_number !== null) {
                $normalizedRegistration = preg_replace('/\s+/', ' ', trim((string) $business->registration_number)) ?? '';
                $business->registration_number = Str::upper($normalizedRegistration);
            }

            if ($business->vibe_unique_id === null || trim((string) $business->vibe_unique_id) === '') {
                $business->vibe_unique_id = 'VIBE-'.strtoupper(Str::ulid());
            }
        });

        static::created(function (self $business): void {
            $speciesIds = Species::query()->where('is_active', true)->pluck('id');
            if ($speciesIds->isNotEmpty()) {
                $business->configuredSpecies()->syncWithoutDetaching($speciesIds->all());
            }

            $unitIds = Unit::query()->where('is_active', true)->pluck('id');
            if ($unitIds->isNotEmpty()) {
                $business->configuredUnits()->syncWithoutDetaching($unitIds->all());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Business (1) → Many Facilities */
    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    public function countryDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'country_id');
    }

    public function provinceDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'province_id');
    }

    public function districtDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'district_id');
    }

    public function sectorDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'sector_id');
    }

    public function cellDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'cell_id');
    }

    public function villageDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'village_id');
    }

    public function ownershipMembers(): HasMany
    {
        return $this->hasMany(BusinessOwnershipMember::class)->orderBy('sort_order');
    }

    /** Users assigned to this business through the business_user role pivot. */
    public function memberUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_user')
            ->withPivot('role')
            ->withTimestamps()
            ->using(BusinessUser::class);
    }

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    public function configuredSpecies(): BelongsToMany
    {
        return $this->belongsToMany(Species::class, 'business_species')->withTimestamps();
    }

    public function configuredUnits(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'business_units')->withTimestamps();
    }

    public function activeConfiguredSpecies(): Collection
    {
        return $this->configuredSpecies()
            ->where('species.is_active', true)
            ->orderBy('species.sort_order')
            ->orderBy('species.name')
            ->get();
    }

    public function activeConfiguredUnits(): Collection
    {
        return $this->configuredUnits()
            ->where('units.is_active', true)
            ->orderBy('units.sort_order')
            ->orderBy('units.name')
            ->get();
    }

    /** Supply requests where this business is the processor. */
    public function supplyRequestsAsProcessor(): HasMany
    {
        return $this->hasMany(SupplyRequest::class, 'processor_id');
    }

    /** Supply requests where this business is the farmer. */
    public function supplyRequestsAsFarmer(): HasMany
    {
        return $this->hasMany(SupplyRequest::class, 'farmer_id');
    }

    public function movementPermits(): HasMany
    {
        return $this->hasMany(MovementPermit::class, 'farmer_id');
    }

    public function healthCertificates(): HasMany
    {
        return $this->hasMany(FarmerHealthCertificate::class, 'farmer_id');
    }

    public function hasOwnershipMembers(): bool
    {
        return in_array($this->ownership_type, self::OWNERSHIP_TYPES_WITH_MEMBERS, true);
    }

    /**
     * @return array<string, string> bracket key => translated label
     */
    public static function baselineRevenueBracketOptions(): array
    {
        return [
            self::BASELINE_REVENUE_BRACKET_LT_2M => __('Less than 2 million RWF'),
            self::BASELINE_REVENUE_BRACKET_2M_20M => __('2 million – 20 million RWF'),
            self::BASELINE_REVENUE_BRACKET_20M_100M => __('20 million – 100 million RWF'),
            self::BASELINE_REVENUE_BRACKET_GT_101M => __('More than 101 million RWF'),
        ];
    }

    public static function mapLegacyBaselineRevenueIntegerToBracket(int $n): string
    {
        if ($n < 2_000_000) {
            return self::BASELINE_REVENUE_BRACKET_LT_2M;
        }
        if ($n <= 20_000_000) {
            return self::BASELINE_REVENUE_BRACKET_2M_20M;
        }
        if ($n <= 101_000_000) {
            return self::BASELINE_REVENUE_BRACKET_20M_100M;
        }

        return self::BASELINE_REVENUE_BRACKET_GT_101M;
    }

    public static function baselineRevenueMidpointRwf(?string $bracket): ?float
    {
        if ($bracket === null || $bracket === '') {
            return null;
        }

        return match ($bracket) {
            self::BASELINE_REVENUE_BRACKET_LT_2M => 1_000_000.0,
            self::BASELINE_REVENUE_BRACKET_2M_20M => 11_000_000.0,
            self::BASELINE_REVENUE_BRACKET_20M_100M => 60_500_000.0,
            self::BASELINE_REVENUE_BRACKET_GT_101M => 150_000_000.0,
            default => null,
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
