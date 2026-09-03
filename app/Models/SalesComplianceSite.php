<?php

namespace App\Models;

use App\Support\SalesComplianceCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesComplianceSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'site_type',
        'name',
        'location_address',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'event_type',
        'event_name',
        'contact_name',
        'contact_phone',
        'contact_email',
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

    public function inspections(): HasMany
    {
        return $this->hasMany(SalesComplianceInspection::class, 'site_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'country_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'province_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'district_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'sector_id');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(SalesComplianceEscalation::class, 'site_id');
    }

    public function siteTypeLabel(): string
    {
        return SalesComplianceCatalog::siteTypeLabel($this->site_type);
    }

    public function locationDisplay(): string
    {
        $parts = collect([
            $this->province?->name,
            $this->district?->name,
            $this->sector?->name,
        ])->filter();

        if ($parts->isNotEmpty()) {
            return $parts->implode(', ');
        }

        return (string) ($this->location_address ?: '—');
    }
}
