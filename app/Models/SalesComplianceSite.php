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
        'latitude',
        'longitude',
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
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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
        $geo = ($this->latitude !== null && $this->longitude !== null)
            ? sprintf('%s, %s', $this->latitude, $this->longitude)
            : null;

        return implode(' · ', array_filter([$this->location_address, $geo]));
    }
}
