<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One individual animal received in an intake session.
 *
 * Health status (healthy / under_observation / rejected) is separate from the
 * parent AnimalIntake session status (received / approved / rejected).
 */
class AnimalIntakeItem extends Model
{
    use HasFactory;

    public const HEALTH_HEALTHY = 'healthy';

    public const HEALTH_OBSERVATION = 'under_observation';

    public const HEALTH_REJECTED = 'rejected';

    public const HEALTH_STATUSES = [
        self::HEALTH_HEALTHY,
        self::HEALTH_OBSERVATION,
        self::HEALTH_REJECTED,
    ];

    public const BODY_CONDITIONS = [
        'poor',
        'fair',
        'good',
        'excellent',
    ];

    protected $fillable = [
        'animal_intake_id',
        'ear_tag',
        'species',
        'sex',
        'age_months',
        'live_weight_kg',
        'body_condition_score',
        'unit_price',
        'health_status',
        'notes',
        'slaughter_plan_id',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'live_weight_kg' => 'decimal:2',
            'age_months' => 'integer',
        ];
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(AnimalIntake::class, 'animal_intake_id');
    }

    public function slaughterPlan(): BelongsTo
    {
        return $this->belongsTo(SlaughterPlan::class);
    }

    public function isHealthy(): bool
    {
        return $this->health_status === self::HEALTH_HEALTHY;
    }

    public function isUnderObservation(): bool
    {
        return $this->health_status === self::HEALTH_OBSERVATION;
    }

    public function isRejected(): bool
    {
        return $this->health_status === self::HEALTH_REJECTED;
    }

    public function isAssignedToPlan(): bool
    {
        return $this->slaughter_plan_id !== null;
    }

    public function isAvailableForPlanning(): bool
    {
        return ! $this->isRejected() && ! $this->isAssignedToPlan();
    }

    public function getHealthStatusLabelAttribute(): string
    {
        return match ($this->health_status) {
            self::HEALTH_HEALTHY => __('Healthy'),
            self::HEALTH_OBSERVATION => __('Under observation'),
            self::HEALTH_REJECTED => __('Rejected'),
            default => (string) $this->health_status,
        };
    }

    public function getBodyConditionLabelAttribute(): ?string
    {
        if ($this->body_condition_score === null || $this->body_condition_score === '') {
            return null;
        }

        return ucfirst((string) $this->body_condition_score);
    }

    /**
     * @param  Builder<AnimalIntakeItem>  $query
     * @return Builder<AnimalIntakeItem>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('health_status', '!=', self::HEALTH_REJECTED)
            ->whereNull('slaughter_plan_id');
    }

    /**
     * @param  Builder<AnimalIntakeItem>  $query
     * @return Builder<AnimalIntakeItem>
     */
    public function scopeBySpecies(Builder $query, string $species): Builder
    {
        return $query->where('species', $species);
    }

    /**
     * @param  Builder<AnimalIntakeItem>  $query
     * @return Builder<AnimalIntakeItem>
     */
    public function scopeHealthy(Builder $query): Builder
    {
        return $query->where('health_status', self::HEALTH_HEALTHY);
    }

    /**
     * @param  Builder<AnimalIntakeItem>  $query
     * @return Builder<AnimalIntakeItem>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('health_status', self::HEALTH_REJECTED);
    }

    // --- Section 2 ---

    /**
     * Per-animal ante-mortem outcomes recorded for this animal.
     */
    public function anteMortemInspectionItems(): HasMany
    {
        return $this->hasMany(AnteMortemInspectionItem::class, 'animal_intake_item_id');
    }

    /**
     * The outcome from the most recent ante-mortem inspection for this animal.
     * Returns null if no outcomes have been recorded.
     */
    public function latestAnteMortemOutcome(): ?string
    {
        return $this->anteMortemInspectionItems()
            ->latest('id')
            ->value('outcome');
    }

    /**
     * True when this animal was approved at its most recent ante-mortem inspection.
     */
    public function wasApprovedAtAnteMortem(): bool
    {
        return $this->latestAnteMortemOutcome() === AnteMortemInspectionItem::OUTCOME_APPROVED;
    }

    /**
     * True when this animal was rejected at its most recent ante-mortem inspection.
     */
    public function wasRejectedAtAnteMortem(): bool
    {
        return $this->latestAnteMortemOutcome() === AnteMortemInspectionItem::OUTCOME_REJECTED;
    }

    /**
     * Slaughter execution records for this individual animal.
     */
    // --- Section 2 ---
    public function slaughterExecutionItems(): HasMany
    {
        return $this->hasMany(SlaughterExecutionItem::class, 'animal_intake_item_id');
    }

    /**
     * True when this animal has at least one slaughter execution record.
     */
    // --- Section 2 ---
    public function wasSlaughtered(): bool
    {
        return $this->slaughterExecutionItems()->exists();
    }

    /**
     * Total dressed/carcass weight recorded across all slaughter executions for this animal.
     * Should normally be one record — returns sum defensively.
     */
    // --- Section 2 ---
    public function totalMeatQuantityKg(): float
    {
        return (float) $this->slaughterExecutionItems()->sum('meat_quantity_kg');
    }

    /**
     * Yield percentage: meat quantity as a percentage of live weight.
     * Returns null when live_weight_kg is not recorded.
     */
    // --- Section 2 ---
    public function meatYieldPercent(): ?float
    {
        if (! $this->live_weight_kg || $this->live_weight_kg <= 0) {
            return null;
        }

        return round($this->totalMeatQuantityKg() / $this->live_weight_kg * 100, 1);
    }

    // --- Section 1 ---

    /**
     * Batch item rows that include this intake animal.
     */
    public function batchItems(): HasMany
    {
        return $this->hasMany(BatchItem::class, 'animal_intake_item_id');
    }

    /**
     * True when this animal has been assigned to at least one batch.
     */
    public function isInBatch(): bool
    {
        if ($this->relationLoaded('batchItems')) {
            return $this->batchItems->isNotEmpty();
        }

        return $this->batchItems()->exists();
    }
}
