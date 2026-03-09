<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'contact_person',
        'email',
        'phone',
        'country',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'business_type',
        'address_line_1',
        'address_line_2',
        'city',
        'state_region',
        'postal_code',
        'tax_id',
        'registration_number',
        'preferred_facility_id',
        'preferred_species',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function countryDivision(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AdministrativeDivision::class, 'country_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AdministrativeDivision::class, 'province_id');
    }

    public function districtDivision(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AdministrativeDivision::class, 'district_id');
    }

    public function sectorDivision(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AdministrativeDivision::class, 'sector_id');
    }

    public function cell(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AdministrativeDivision::class, 'cell_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AdministrativeDivision::class, 'village_id');
    }

    public function preferredFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'preferred_facility_id');
    }

    public const BUSINESS_TYPE_BUTCHERY = 'butchery';
    public const BUSINESS_TYPE_RESTAURANT = 'restaurant';
    public const BUSINESS_TYPE_DISTRIBUTOR = 'distributor';
    public const BUSINESS_TYPE_SUPERMARKET = 'supermarket';
    public const BUSINESS_TYPE_OTHER = 'other';

    public const BUSINESS_TYPES = [
        self::BUSINESS_TYPE_BUTCHERY => 'Butchery',
        self::BUSINESS_TYPE_RESTAURANT => 'Restaurant',
        self::BUSINESS_TYPE_DISTRIBUTOR => 'Distributor',
        self::BUSINESS_TYPE_SUPERMARKET => 'Supermarket',
        self::BUSINESS_TYPE_OTHER => 'Other',
    ];

    public function deliveryConfirmations(): HasMany
    {
        return $this->hasMany(DeliveryConfirmation::class);
    }

    public function demands(): HasMany
    {
        return $this->hasMany(Demand::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClientActivity::class)->orderByDesc('occurred_at');
    }

    /** Location string from divisions (Country → Province → District → Sector → Cell → Village) or legacy country. */
    public function getLocationLineAttribute(): string
    {
        if ($this->country_id && $this->relationLoaded('countryDivision') && $this->countryDivision) {
            $parts = array_filter([
                $this->countryDivision->name ?? null,
                $this->province?->name ?? null,
                $this->districtDivision?->name ?? null,
                $this->sectorDivision?->name ?? null,
                $this->cell?->name ?? null,
                $this->village?->name ?? null,
            ]);
            return implode(', ', $parts) ?: '—';
        }
        return $this->country ?? '—';
    }

    /** Single-line address for display (street + city, country, etc.). */
    public function getAddressLineAttribute(): string
    {
        $street = array_filter([$this->address_line_1, $this->address_line_2]);
        $location = $this->location_line;
        $parts = array_merge($street, $location !== '—' ? [$location] : [], array_filter([
            $this->city,
            $this->state_region,
            $this->postal_code,
            $this->country_id ? null : $this->country,
        ]));
        return implode(', ', $parts) ?: '—';
    }

    /** Display name: name + country for lists. */
    public function getDisplayNameAttribute(): string
    {
        $countryName = $this->countryDivision?->name ?? $this->country ?? '';
        return trim($this->name . ($countryName ? ' (' . $countryName . ')' : ''));
    }
}
