<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'business_name',
        'registration_number',
        'tax_id',
        'contact_phone',
        'email',
        'status',
        // Ownership info
        'owner_first_name',
        'owner_last_name',
        'owner_dob',
        'owner_name',
        'owner_phone',
        'owner_email',
        'ownership_type',
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

    protected $casts = [
        'owner_dob' => 'date',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
    ];

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

    /** Users who are members of this business (manager/staff), not the owner. */
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

    public function hasOwnershipMembers(): bool
    {
        return in_array($this->ownership_type, self::OWNERSHIP_TYPES_WITH_MEMBERS, true);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
