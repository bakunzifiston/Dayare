<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspector extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'first_name',
        'last_name',
        'national_id',
        'phone_number',
        'email',
        'dob',
        'nationality',
        'country',
        'district',
        'sector',
        'cell',
        'village',
        'authorization_number',
        'authorization_issue_date',
        'authorization_expiry_date',
        'species_allowed',
        'daily_capacity',
        'stamp_serial_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'authorization_issue_date' => 'date',
            'authorization_expiry_date' => 'date',
        ];
    }

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
    ];

    /** Inspector belongs to one Facility. Facility (1) → Many Inspectors */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /** Inspector (1) → Many Slaughter Sessions */
    public function slaughterPlans(): HasMany
    {
        return $this->hasMany(SlaughterPlan::class);
    }

    /** Inspector (1) → Many Inspections (e.g. ante-mortem) */
    public function anteMortemInspections(): HasMany
    {
        return $this->hasMany(AnteMortemInspection::class);
    }

    /** Inspector (1) → Many Certificates */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /** Inspector (1) → Many Batches */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /** Inspector (1) → Many Post-Mortem Inspections */
    public function postMortemInspections(): HasMany
    {
        return $this->hasMany(PostMortemInspection::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isAuthorizationExpired(): bool
    {
        return $this->authorization_expiry_date && $this->authorization_expiry_date->isPast();
    }
}
