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
        'owner_national_id',
        'owner_dob',
        'owner_gender',
        'owner_pwd_status',
        'owner_name',
        'owner_phone',
        'owner_email',
        'owner_emergency_contact',
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
        // Slaughterhouse survey
        'total_members',
        'female_members',
        'members_18_35',
        'young_women_members',
        'animals_processed',
        'animals_processed_other',
        'daily_processing',
        'products_sold',
        'products_sold_other',
        'customer_segments',
        'customer_segments_other',
        'daily_sales_kg',
        'buyer_count',
        'contract_type',
        'contracted_buyers',
        'digital_marketplace',
        'digital_marketplace_name',
        'baseline_revenue_rwf',
        'has_receiving_area',
        'road_condition',
        'has_potable_water',
        'waste_system',
        'has_cold_storage',
        'cold_storage_capacity_kg',
        'sanitary_certificate',
        'sanitary_certificate_expiry',
        'waste_disposal_plan',
        'has_sops',
        'workers_trained',
        'total_employees',
        'female_employees',
        'employees_18_35',
        'female_employees_18_35',
        'pwd_employees',
        'refugee_employees',
        'seasonal_workers',
        'has_dedicated_manager',
        'manager_first_name',
        'manager_gender',
        'manager_age',
        'bank_account',
        'uses_mobile_money',
        'digital_payment_willingness',
        'uses_digital_records',
        'digital_system_name',
        'digital_devices',
        'network_connectivity',
        'digital_ledger_willingness',
        'supporting_documents',
        'supporting_documents_other',
        'supporting_document_files',
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

    public const ANIMAL_CATTLE = 'cattle';

    public const ANIMAL_GOATS = 'goats';

    public const ANIMAL_SHEEP = 'sheep';

    public const ANIMAL_PIGS = 'pigs';

    public const ANIMAL_POULTRY = 'poultry';

    public const ANIMAL_OTHER = 'other';

    /** @var list<string> */
    public const ANIMALS_PROCESSED = [
        self::ANIMAL_CATTLE,
        self::ANIMAL_GOATS,
        self::ANIMAL_SHEEP,
        self::ANIMAL_PIGS,
        self::ANIMAL_POULTRY,
        self::ANIMAL_OTHER,
    ];

    public const PRODUCT_FRESH_MEAT = 'fresh_meat';

    public const PRODUCT_PROCESSED_MEAT = 'processed_meat';

    public const PRODUCT_BY_PRODUCTS = 'by_products';

    public const PRODUCT_OTHER = 'other';

    /** @var list<string> */
    public const PRODUCTS_SOLD = [
        self::PRODUCT_FRESH_MEAT,
        self::PRODUCT_PROCESSED_MEAT,
        self::PRODUCT_BY_PRODUCTS,
        self::PRODUCT_OTHER,
    ];

    public const SEGMENT_BUTCHERIES = 'butcheries';

    public const SEGMENT_SUPERMARKETS = 'supermarkets';

    public const SEGMENT_HORECA = 'hotels_restaurants';

    public const SEGMENT_INSTITUTIONS = 'schools_institutions';

    public const SEGMENT_HOUSEHOLDS = 'individual_households';

    public const SEGMENT_EXPORT = 'export_buyers';

    public const SEGMENT_OTHER = 'other';

    /** @var list<string> */
    public const CUSTOMER_SEGMENTS = [
        self::SEGMENT_BUTCHERIES,
        self::SEGMENT_SUPERMARKETS,
        self::SEGMENT_HORECA,
        self::SEGMENT_INSTITUTIONS,
        self::SEGMENT_HOUSEHOLDS,
        self::SEGMENT_EXPORT,
        self::SEGMENT_OTHER,
    ];

    public const CONTRACT_WRITTEN = 'written_contracts';

    public const CONTRACT_VERBAL = 'verbal_agreements';

    public const CONTRACT_NONE = 'no_formal_contracts';

    /** @var list<string> */
    public const CONTRACT_TYPES = [
        self::CONTRACT_WRITTEN,
        self::CONTRACT_VERBAL,
        self::CONTRACT_NONE,
    ];

    public const ROAD_GOOD = 'good';

    public const ROAD_FAIR = 'fair';

    public const ROAD_POOR = 'poor';

    /** @var list<string> */
    public const ROAD_CONDITIONS = [self::ROAD_GOOD, self::ROAD_FAIR, self::ROAD_POOR];

    public const WASTE_FUNCTIONAL = 'functional';

    public const WASTE_NEEDS_IMPROVEMENT = 'needs_improvement';

    public const WASTE_NONE = 'none';

    /** @var list<string> */
    public const WASTE_SYSTEMS = [self::WASTE_FUNCTIONAL, self::WASTE_NEEDS_IMPROVEMENT, self::WASTE_NONE];

    public const MANAGER_FULL_TIME = 'full_time';

    public const MANAGER_SELF = 'self_managed';

    public const MANAGER_NONE = 'no_manager';

    /** @var list<string> */
    public const DEDICATED_MANAGER_OPTIONS = [
        self::MANAGER_FULL_TIME,
        self::MANAGER_SELF,
        self::MANAGER_NONE,
    ];

    public const BANK_BUSINESS = 'business_account';

    public const BANK_PERSONAL = 'personal_account';

    public const BANK_NONE = 'no_account';

    /** @var list<string> */
    public const BANK_ACCOUNT_TYPES = [self::BANK_BUSINESS, self::BANK_PERSONAL, self::BANK_NONE];

    public const MOBILE_YES = 'yes';

    public const MOBILE_SOMETIMES = 'sometimes';

    public const MOBILE_NO = 'no';

    /** @var list<string> */
    public const MOBILE_MONEY_USAGE = [self::MOBILE_YES, self::MOBILE_SOMETIMES, self::MOBILE_NO];

    public const PAY_PREFER_DIGITAL = 'prefer_digital';

    public const PAY_WILLING_TRY = 'willing_try';

    public const PAY_UNSURE = 'unsure';

    public const PAY_PREFER_CASH = 'prefer_cash';

    /** @var list<string> */
    public const DIGITAL_PAYMENT_WILLINGNESS = [
        self::PAY_PREFER_DIGITAL,
        self::PAY_WILLING_TRY,
        self::PAY_UNSURE,
        self::PAY_PREFER_CASH,
    ];

    public const DEVICE_DESKTOP = 'desktop_laptop';

    public const DEVICE_TABLET = 'tablet';

    public const DEVICE_SMARTPHONE = 'smartphone';

    public const DEVICE_BASIC_PHONE = 'basic_phone';

    public const DEVICE_NONE = 'none';

    /** @var list<string> */
    public const DIGITAL_DEVICES = [
        self::DEVICE_DESKTOP,
        self::DEVICE_TABLET,
        self::DEVICE_SMARTPHONE,
        self::DEVICE_BASIC_PHONE,
        self::DEVICE_NONE,
    ];

    public const NET_STRONG = 'strong_4g';

    public const NET_MODERATE = 'moderate_3g';

    public const NET_WEAK = 'weak_intermittent';

    public const NET_OFFLINE = 'no_signal';

    /** @var list<string> */
    public const NETWORK_CONNECTIVITY = [self::NET_STRONG, self::NET_MODERATE, self::NET_WEAK, self::NET_OFFLINE];

    public const LEDGER_FULLY = 'fully_willing';

    public const LEDGER_TRAINING = 'needs_training';

    public const LEDGER_UNSURE = 'unsure';

    public const LEDGER_PREFER_CURRENT = 'prefer_current';

    /** @var list<string> */
    public const DIGITAL_LEDGER_WILLINGNESS = [
        self::LEDGER_FULLY,
        self::LEDGER_TRAINING,
        self::LEDGER_UNSURE,
        self::LEDGER_PREFER_CURRENT,
    ];

    public const DOC_LICENSE = 'license_registration';

    public const DOC_HEALTH = 'health_sanitary_certificate';

    public const DOC_SOPS = 'sops_document';

    public const DOC_FLOOR_PLAN = 'floor_plan';

    public const DOC_WASTE_PLAN = 'waste_management_plan';

    public const DOC_OTHER = 'other';

    /** @var list<string> */
    public const SUPPORTING_DOCUMENTS = [
        self::DOC_LICENSE,
        self::DOC_HEALTH,
        self::DOC_SOPS,
        self::DOC_FLOOR_PLAN,
        self::DOC_WASTE_PLAN,
        self::DOC_OTHER,
    ];

    protected $casts = [
        'owner_dob' => 'date',
        'vibe_commencement_date' => 'date',
        'sanitary_certificate_expiry' => 'date',
        'animals_processed' => 'array',
        'daily_processing' => 'array',
        'products_sold' => 'array',
        'customer_segments' => 'array',
        'daily_sales_kg' => 'array',
        'digital_devices' => 'array',
        'supporting_documents' => 'array',
        'supporting_document_files' => 'array',
        'digital_marketplace' => 'boolean',
        'has_receiving_area' => 'boolean',
        'has_potable_water' => 'boolean',
        'has_cold_storage' => 'boolean',
        'sanitary_certificate' => 'boolean',
        'waste_disposal_plan' => 'boolean',
        'has_sops' => 'boolean',
        'workers_trained' => 'boolean',
        'uses_digital_records' => 'boolean',
        'baseline_revenue_rwf' => 'decimal:2',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
    ];

    public static function normalizeDisplayName(string $businessName): string
    {
        $trimmed = trim($businessName);

        return (string) preg_replace('/\s+/', ' ', Str::lower($trimmed));
    }

    /**
     * Optional wizard count fields that must not be persisted as null.
     *
     * @var list<string>
     */
    public const OPTIONAL_COUNT_FIELDS = [
        'total_members',
        'female_members',
        'members_18_35',
        'young_women_members',
        'buyer_count',
        'cold_storage_capacity_kg',
        'total_employees',
        'female_employees',
        'employees_18_35',
        'female_employees_18_35',
        'pwd_employees',
        'refugee_employees',
        'seasonal_workers',
        'full_time_employees',
        'workers_with_disabilities',
        'refugee_workers',
        'manager_age',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $business): void {
            foreach (self::OPTIONAL_COUNT_FIELDS as $field) {
                if (array_key_exists($field, $business->getAttributes()) && $business->{$field} === null) {
                    $business->{$field} = 0;
                }
            }

            if ($business->business_name !== null) {
                $collapsedWhitespaceName = preg_replace('/\s+/', ' ', trim((string) $business->business_name)) ?? '';
                $business->business_name = $collapsedWhitespaceName;
                $business->business_name_normalized = self::normalizeDisplayName($collapsedWhitespaceName);
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

    /**
     * Individual registered owner (first + last name), for traceability / passport labels.
     * Intentionally excludes legal business or cooperative name.
     */
    public function ownerIndividualDisplayName(): string
    {
        $first = trim((string) $this->owner_first_name);
        $last = trim((string) $this->owner_last_name);
        $fromParts = trim($first.' '.$last);
        if ($fromParts !== '') {
            return $fromParts;
        }

        $single = trim((string) $this->owner_name);
        if ($single !== '') {
            return $single;
        }

        return '';
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

    /** @return array<string, string> */
    public static function animalsProcessedLabelMap(): array
    {
        return [
            self::ANIMAL_CATTLE => __('Cattle'),
            self::ANIMAL_GOATS => __('Goats'),
            self::ANIMAL_SHEEP => __('Sheep'),
            self::ANIMAL_PIGS => __('Pigs'),
            self::ANIMAL_POULTRY => __('Poultry'),
            self::ANIMAL_OTHER => __('Other'),
        ];
    }

    /** @return array<string, string> */
    public static function productsSoldLabelMap(): array
    {
        return [
            self::PRODUCT_FRESH_MEAT => __('Fresh meat (unprocessed)'),
            self::PRODUCT_PROCESSED_MEAT => __('Processed meat (sausages/mince etc.)'),
            self::PRODUCT_BY_PRODUCTS => __('By-products (hides/skins/offal/bones/blood)'),
            self::PRODUCT_OTHER => __('Other'),
        ];
    }

    /** @return array<string, string> */
    public static function customerSegmentsLabelMap(): array
    {
        return [
            self::SEGMENT_BUTCHERIES => __('Butcheries'),
            self::SEGMENT_SUPERMARKETS => __('Supermarkets'),
            self::SEGMENT_HORECA => __('Hotels & Restaurants'),
            self::SEGMENT_INSTITUTIONS => __('Schools/Institutions'),
            self::SEGMENT_HOUSEHOLDS => __('Individual households'),
            self::SEGMENT_EXPORT => __('Export buyers'),
            self::SEGMENT_OTHER => __('Other'),
        ];
    }

    /** @return array<string, string> */
    public static function digitalDevicesLabelMap(): array
    {
        return [
            self::DEVICE_DESKTOP => __('Desktop/Laptop'),
            self::DEVICE_TABLET => __('Tablet'),
            self::DEVICE_SMARTPHONE => __('Smartphone'),
            self::DEVICE_BASIC_PHONE => __('Basic phone only'),
            self::DEVICE_NONE => __('None of the above'),
        ];
    }

    /** @return array<string, string> */
    public static function supportingDocumentsLabelMap(): array
    {
        return [
            self::DOC_LICENSE => __('Copy of License/Registration'),
            self::DOC_HEALTH => __('Health/Sanitary Certificate'),
            self::DOC_SOPS => __('SOPs Document'),
            self::DOC_FLOOR_PLAN => __('Floor Plan/Facility Map'),
            self::DOC_WASTE_PLAN => __('Waste Management Plan'),
            self::DOC_OTHER => __('Other'),
        ];
    }
}
