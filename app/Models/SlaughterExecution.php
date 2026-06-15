<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Slaughter Execution – record of actual slaughter for a session.
 * SlaughterSession (1) → Many SlaughterExecution.
 */
class SlaughterExecution extends Model
{
    use HasFactory;

    protected $table = 'slaughter_executions';

    protected $fillable = [
        'slaughter_plan_id',
        'actual_animals_slaughtered',
        // --- Section 2 ---
        'slaughter_count_source',
        'slaughter_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'slaughter_time' => 'datetime',
        ];
    }

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    // --- Section 2 ---
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_ITEMS = 'from_assigned_items';

    /** Belongs to one SlaughterSession */
    public function slaughterPlan(): BelongsTo
    {
        return $this->belongsTo(SlaughterPlan::class);
    }

    /** SlaughterExecution (1) → Many Batches */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Per-animal slaughter records for this execution.
     */
    // --- Section 2 ---
    public function executionItems(): HasMany
    {
        return $this->hasMany(SlaughterExecutionItem::class, 'slaughter_execution_id');
    }

    /**
     * Total dressed/carcass meat weight across all slaughtered animals in kg.
     */
    // --- Section 2 ---
    public function getTotalMeatQuantityKgAttribute(): float
    {
        if ($this->relationLoaded('executionItems')) {
            return (float) $this->executionItems->sum('meat_quantity_kg');
        }

        return (float) $this->executionItems()->sum('meat_quantity_kg');
    }

    /**
     * Count of individual animals with recorded slaughter data.
     * Uses eager-loaded relation when available to avoid N+1 on hub/index.
     */
    // --- Section 2 ---
    public function getSlaughteredCountFromItemsAttribute(): int
    {
        if ($this->relationLoaded('executionItems')) {
            return $this->executionItems->count();
        }

        return $this->executionItems()->count();
    }

    /**
     * True when at least one per-animal slaughter record exists.
     */
    // --- Section 2 ---
    public function hasPerAnimalSlaughter(): bool
    {
        if ($this->relationLoaded('executionItems')) {
            return $this->executionItems->isNotEmpty();
        }

        return $this->executionItems()->exists();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Completed executions at the same facility on the same calendar day as this execution.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<SlaughterExecution>  $query
     */
    public function scopeSameDayAndFacility($query, SlaughterExecution $reference): void
    {
        $reference->loadMissing('slaughterPlan');

        $query
            ->where('status', self::STATUS_COMPLETED)
            ->whereDate('slaughter_time', $reference->slaughter_time)
            ->whereHas(
                'slaughterPlan',
                fn ($planQuery) => $planQuery->where('facility_id', $reference->slaughterPlan->facility_id),
            );
    }

    /**
     * Latest ante-mortem inspection for this execution's plan, when available.
     */
    public function latestAnteMortemInspection(): ?AnteMortemInspection
    {
        $plan = $this->slaughterPlan;
        if ($plan === null) {
            return null;
        }

        if ($plan->relationLoaded('anteMortemInspections')) {
            return $plan->anteMortemInspections->sortBy('inspection_date')->last();
        }

        return $plan->anteMortemInspections()->latest('inspection_date')->first();
    }

    /**
     * Hours from ante-mortem inspection end-of-day to slaughter time.
     * Negative when slaughter is before the inspection date.
     */
    public function hoursFromAnteMortemEndOfDay(): ?float
    {
        $latestAM = $this->latestAnteMortemInspection();
        if ($latestAM === null) {
            return null;
        }

        $amEnd = $latestAM->inspection_date->copy()->endOfDay();

        return (float) $amEnd->diffInHours($this->slaughter_time, false);
    }

    /**
     * Whether slaughter occurred more than 24 hours after ante-mortem end-of-day.
     */
    public function exceedsAnteMortemWindow(): bool
    {
        $hours = $this->hoursFromAnteMortemEndOfDay();

        return $hours !== null && $hours > 24;
    }

    /**
     * Human-readable ante-mortem window note for reports (null when within window or no AM).
     */
    public function anteMortemWindowReportNote(): ?string
    {
        $latestAM = $this->latestAnteMortemInspection();
        if ($latestAM === null) {
            return null;
        }

        $hours = $this->hoursFromAnteMortemEndOfDay();
        if ($hours === null) {
            return null;
        }

        if ($hours > 24) {
            $deadline = $latestAM->inspection_date->copy()->endOfDay()->addHours(24);

            return __('Slaughter occurred :hours hours after the 24-hour ante-mortem window (deadline was :deadline). Ante-mortem was recorded on :date.', [
                'hours' => number_format($hours - 24, 1),
                'deadline' => $deadline->format('d M Y H:i'),
                'date' => $latestAM->inspection_date->format('d M Y'),
            ]);
        }

        if ($hours > 20) {
            return __('Slaughter occurred within the ante-mortem window, with less than :remaining hour(s) remaining before the deadline.', [
                'remaining' => (int) ceil(24 - $hours),
            ]);
        }

        return null;
    }
}
