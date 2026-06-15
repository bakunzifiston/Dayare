<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'animal_intake_id',
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

    /** SlaughterPlan belongs to AnimalIntake (required before slaughter). */
    public function animalIntake(): BelongsTo
    {
        return $this->belongsTo(AnimalIntake::class);
    }

    /** Alias for {@see animalIntake()} — used in hub eager-loading and views. */
    public function intake(): BelongsTo
    {
        return $this->belongsTo(AnimalIntake::class, 'animal_intake_id');
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

    // --- Section D ---

    /** Individual animals assigned to this slaughter plan. */
    public function assignedItems(): HasMany
    {
        return $this->hasMany(AnimalIntakeItem::class, 'slaughter_plan_id');
    }

    public function getAssignedCountAttribute(): int
    {
        if ($this->relationLoaded('assignedItems')) {
            return $this->assignedItems->count();
        }

        return $this->assignedItems()->count();
    }

    public function isFullyAssigned(): bool
    {
        return $this->assigned_count === (int) $this->number_of_animals_scheduled;
    }

    public function hasAssignmentGap(): bool
    {
        return $this->animal_intake_id !== null
            && $this->assignedItems()->count() === 0;
    }

    /** Label for slaughter session dropdowns (ante-mortem, execution, etc.). */
    public function sessionSelectLabel(): string
    {
        $date = $this->slaughter_date?->format('d M Y') ?? '—';
        $facility = $this->facility?->facility_name ?? '—';
        $species = $this->species ?? '—';
        $animalCount = $this->assigned_count > 0
            ? $this->assigned_count
            : (int) $this->number_of_animals_scheduled;
        $intakeRef = $this->intake?->reference
            ?? $this->animalIntake?->reference
            ?? ($this->animal_intake_id ? 'INT-'.$this->animal_intake_id : '—');

        return $date.' — '.$facility.' ('.$species.') · '.$animalCount.' '
            .($animalCount === 1 ? __('animal') : __('animals')).' · '.$intakeRef;
    }

    /**
     * @param  Builder<SlaughterPlan>  $query
     * @return Builder<SlaughterPlan>
     */
    public function scopeWithAssignmentGap(Builder $query): Builder
    {
        return $query->whereNotNull('animal_intake_id')
            ->whereDoesntHave('assignedItems');
    }
}
