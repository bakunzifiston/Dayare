<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
     * Executions at the same facility on the same calendar day as this execution.
     *
     * Defaults to completed only (batching). Pass statuses to include in-progress
     * sessions when loading slaughtered animals for post-mortem.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<SlaughterExecution>  $query
     * @param  list<string>|null  $statuses
     */
    public function scopeSameDayAndFacility($query, SlaughterExecution $reference, ?array $statuses = null): void
    {
        $reference->loadMissing('slaughterPlan');

        $query
            ->whereIn('status', $statuses ?? [self::STATUS_COMPLETED])
            ->whereDate('slaughter_time', $reference->slaughter_time)
            ->whereHas(
                'slaughterPlan',
                fn ($planQuery) => $planQuery->where('facility_id', $reference->slaughterPlan->facility_id),
            );
    }

    /**
     * Same-day facility executions that can contribute slaughtered animals to post-mortem.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<SlaughterExecution>  $query
     */
    public function scopeSameDayAndFacilityForPostMortem($query, SlaughterExecution $reference): void
    {
        $this->scopeSameDayAndFacility($query, $reference, [
            self::STATUS_COMPLETED,
            self::STATUS_IN_PROGRESS,
        ]);
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

    /**
     * Animals slaughtered in this execution scope (same facility and day) available for post-mortem.
     *
     * @return Collection<int, array{
     *     batch_item_id: int|null,
     *     slaughter_execution_item_id: int,
     *     animal_intake_item_id: int,
     *     ear_tag: string,
     *     tag_number: string|null,
     *     species: string,
     *     sex: string,
     *     meat_quantity_kg: float,
     *     session_label: string,
     *     source: string
     * }>
     */
    public function inspectableAnimalsForPostMortem(): Collection
    {
        $this->loadMissing(['slaughterPlan', 'executionItems.intakeItem.intake']);

        $executionIds = self::query()
            ->sameDayAndFacilityForPostMortem($this)
            ->pluck('id');

        return SlaughterExecutionItem::query()
            ->whereIn('slaughter_execution_id', $executionIds)
            ->with(['intakeItem.intake', 'execution'])
            ->orderBy('id')
            ->get()
            ->unique('animal_intake_item_id')
            ->map(function (SlaughterExecutionItem $executionItem) {
                $intake = $executionItem->intakeItem;

                return [
                    'batch_item_id' => null,
                    'slaughter_execution_item_id' => $executionItem->id,
                    'animal_intake_item_id' => (int) $executionItem->animal_intake_item_id,
                    'ear_tag' => $intake->ear_tag,
                    'tag_number' => filled($intake->intake?->species_ear_tag)
                        ? (string) $intake->intake->species_ear_tag
                        : null,
                    'species' => $intake->species,
                    'sex' => ucfirst($intake->sex),
                    'meat_quantity_kg' => (float) $executionItem->meat_quantity_kg,
                    'session_label' => $executionItem->execution?->slaughter_time?->format('H:i') ?? '—',
                    'source' => 'execution',
                ];
            })
            ->values();
    }

    /**
     * Whether every slaughtered animal in this execution scope has a post-mortem outcome.
     */
    public function isPostMortemComplete(): bool
    {
        $animals = $this->inspectableAnimalsForPostMortem();
        if ($animals->isEmpty()) {
            return false;
        }

        $inspectedIds = $this->inspectedAnimalIntakeItemIds();

        return $animals->every(
            fn (array $animal) => $inspectedIds->contains((int) $animal['animal_intake_item_id']),
        );
    }

    /**
     * @return Collection<int, int>
     */
    public function inspectedAnimalIntakeItemIds(): Collection
    {
        $batchIds = Batch::query()
            ->whereIn('slaughter_execution_id', self::query()->sameDayAndFacilityForPostMortem($this)->pluck('id'))
            ->pluck('id');

        if ($batchIds->isEmpty()) {
            return collect();
        }

        return PostMortemInspectionItem::query()
            ->whereHas('inspection', fn ($query) => $query->whereIn('batch_id', $batchIds))
            ->pluck('animal_intake_item_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}
