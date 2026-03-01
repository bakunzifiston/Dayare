<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Slaughter Session (SlaughterPlan).
 *
 * Belongs to: Facility, Inspector.
 * Has many: Ante-mortem records (AnteMortemInspection), SlaughterExecution.
 */
class SlaughterPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slaughter_date',
        'facility_id',
        'inspector_id',
        'species',
        'number_of_animals_scheduled',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'slaughter_date' => 'date',
        ];
    }

    public const STATUS_PLANNED = 'planned';
    public const STATUS_APPROVED = 'approved';

    public const STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_APPROVED,
    ];

    public const SPECIES_CATTLE = 'Cattle';
    public const SPECIES_GOAT = 'Goat';
    public const SPECIES_SHEEP = 'Sheep';
    public const SPECIES_PIG = 'Pig';
    public const SPECIES_OTHER = 'Other';

    public const SPECIES_OPTIONS = [
        self::SPECIES_CATTLE,
        self::SPECIES_GOAT,
        self::SPECIES_SHEEP,
        self::SPECIES_PIG,
        self::SPECIES_OTHER,
    ];

    /** SlaughterSession belongs to Facility */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /** SlaughterSession belongs to Inspector */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Inspector::class);
    }

    /** SlaughterSession (1) → Many Ante-mortem records */
    public function anteMortemInspections(): HasMany
    {
        return $this->hasMany(AnteMortemInspection::class);
    }

    /** SlaughterSession (1) → Many SlaughterExecution */
    public function slaughterExecutions(): HasMany
    {
        return $this->hasMany(SlaughterExecution::class);
    }

    public function isPlanned(): bool
    {
        return $this->status === self::STATUS_PLANNED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
