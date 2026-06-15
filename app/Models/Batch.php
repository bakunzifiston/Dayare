<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * Batch – group of carcasses from slaughter.
 * SlaughterExecution (1) → Many Batches.
 * Batch belongs to: SlaughterExecution, Inspector.
 * Batch (1) → One Post-Mortem Inspection.
 */
class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'slaughter_execution_id',
        'inspector_id',
        'species',
        'quantity',
        'quantity_unit',
        'batch_code',
        'status',
        'cold_chain_status',
    ];

    protected function casts(): array
    {
        return [
            // --- Section 1 ---
            'quantity' => 'decimal:2',
        ];
    }

    /** Display label for quantity_unit (from configured Unit name, or Demand legacy label, or code). */
    public function getQuantityUnitLabelAttribute(): string
    {
        $unit = Unit::where('code', $this->quantity_unit)->first();
        if ($unit) {
            return $unit->name;
        }

        return Demand::QUANTITY_UNITS[$this->quantity_unit] ?? (string) $this->quantity_unit;
    }

    /** Cold room / temperature compliance (separate from inspection status). */
    public const COLD_CHAIN_OK = 'ok';

    public const COLD_CHAIN_AT_RISK = 'at_risk';

    public const COLD_CHAIN_COMPROMISED = 'compromised';

    public const COLD_CHAIN_STATUSES = [
        self::COLD_CHAIN_OK,
        self::COLD_CHAIN_AT_RISK,
        self::COLD_CHAIN_COMPROMISED,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
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

    protected static function booted(): void
    {
        static::creating(function (Batch $batch) {
            if (empty($batch->batch_code)) {
                $batch->batch_code = 'BAT-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));
            }
        });
    }

    /** Batch belongs to SlaughterExecution */
    public function slaughterExecution(): BelongsTo
    {
        return $this->belongsTo(SlaughterExecution::class);
    }

    /** Batch belongs to Inspector */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Inspector::class);
    }

    /** Batch (1) → One Post-Mortem Inspection */
    public function postMortemInspection(): HasOne
    {
        return $this->hasOne(PostMortemInspection::class);
    }

    /** Batch (1) → One Certificate (allowed only if post-mortem approved_quantity > 0) */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    /** Batch (1) → Can have One WarehouseStorage */
    public function warehouseStorage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WarehouseStorage::class);
    }

    public function canIssueCertificate(): bool
    {
        if (! $this->postMortemInspection) {
            return false;
        }

        if ($this->postMortemInspection->approved_quantity <= 0) {
            return false;
        }

        if ($this->hasPerAnimalData() && ! $this->isPostMortemComplete()) {
            return false;
        }

        return true;
    }

    public function transportTrips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransportTrip::class);
    }

    // --- Section 1 ---

    /**
     * Individual animals assigned to this batch.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BatchItem::class, 'batch_id')->orderBy('id');
    }

    /**
     * True when this batch has per-animal item rows.
     */
    public function hasPerAnimalData(): bool
    {
        if ($this->relationLoaded('items')) {
            return $this->items->isNotEmpty();
        }

        return $this->items()->exists();
    }

    /**
     * Total meat quantity across all batch items in kg.
     */
    public function getTotalMeatQuantityKgAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum('meat_quantity_kg');
        }

        return (float) $this->items()->sum('meat_quantity_kg');
    }

    /**
     * Number of animals in this batch.
     */
    public function getAnimalCountAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return $this->items->count();
        }

        return $this->items()->count();
    }

    /**
     * Number of batch animals with a recorded post-mortem outcome.
     */
    public function getPostMortemDoneCountAttribute(): int
    {
        return $this->items()->whereHas('postMortemOutcome')->count();
    }

    /**
     * True when every animal in the batch has a post-mortem outcome.
     */
    public function isPostMortemComplete(): bool
    {
        $total = $this->animal_count;
        if ($total === 0) {
            return false;
        }

        return $this->post_mortem_done_count === $total;
    }

    /**
     * Whether cold chain status is normal.
     */
    public function isColdChainOk(): bool
    {
        return $this->cold_chain_status === self::COLD_CHAIN_OK;
    }

    /**
     * Whether cold chain is at risk.
     */
    public function isColdChainAtRisk(): bool
    {
        return $this->cold_chain_status === self::COLD_CHAIN_AT_RISK;
    }

    /**
     * Whether cold chain has been compromised.
     */
    public function isColdChainCompromised(): bool
    {
        return $this->cold_chain_status === self::COLD_CHAIN_COMPROMISED;
    }

    /**
     * Tailwind badge classes for the current cold chain status.
     */
    public function getColdChainBadgeClassAttribute(): string
    {
        return match ($this->cold_chain_status) {
            self::COLD_CHAIN_AT_RISK => 'bg-yellow-100 text-yellow-800',
            self::COLD_CHAIN_COMPROMISED => 'bg-red-100 text-red-800',
            default => 'bg-green-100 text-green-800',
        };
    }

    /**
     * Suggested batch quantity from the linked slaughter execution.
     */
    public function suggestedQuantity(): float|int
    {
        $execution = $this->slaughterExecution;
        if ($execution === null) {
            return 0;
        }

        return $execution->hasPerAnimalSlaughter()
            ? $execution->total_meat_quantity_kg
            : $execution->actual_animals_slaughtered;
    }

    /**
     * Whether a post-mortem inspection record exists for this batch.
     */
    public function hasPostMortem(): bool
    {
        return $this->postMortemInspection()->exists();
    }

    /**
     * Animals available for post-mortem on this batch.
     * Uses batch items when present; otherwise falls back to slaughter execution animals.
     *
     * @return Collection<int, array{
     *     batch_item_id: int|null,
     *     slaughter_execution_item_id: int,
     *     animal_intake_item_id: int,
     *     ear_tag: string,
     *     species: string,
     *     sex: string,
     *     meat_quantity_kg: float,
     *     session_label: string,
     *     source: string
     * }>
     */
    public function inspectableAnimalsForPostMortem(): Collection
    {
        $this->loadMissing([
            'items.intakeItem',
            'slaughterExecution.executionItems.intakeItem',
            'slaughterExecution.slaughterPlan',
        ]);

        if ($this->hasPerAnimalData()) {
            return $this->items->map(function (BatchItem $batchItem) {
                $intake = $batchItem->intakeItem;

                return [
                    'batch_item_id' => $batchItem->id,
                    'slaughter_execution_item_id' => (int) $batchItem->slaughter_execution_item_id,
                    'animal_intake_item_id' => (int) $batchItem->animal_intake_item_id,
                    'ear_tag' => $intake->ear_tag,
                    'species' => $intake->species,
                    'sex' => ucfirst($intake->sex),
                    'meat_quantity_kg' => (float) $batchItem->meat_quantity_kg,
                    'session_label' => $this->slaughterExecution?->slaughter_time?->format('H:i') ?? '—',
                    'source' => 'batch',
                ];
            })->values();
        }

        $reference = $this->slaughterExecution;
        if ($reference === null) {
            return collect();
        }

        $executionIds = SlaughterExecution::query()
            ->sameDayAndFacility($reference)
            ->pluck('id');

        return SlaughterExecutionItem::query()
            ->whereIn('slaughter_execution_id', $executionIds)
            ->with(['intakeItem', 'execution'])
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
                    'species' => $intake->species,
                    'sex' => ucfirst($intake->sex),
                    'meat_quantity_kg' => (float) $executionItem->meat_quantity_kg,
                    'session_label' => $executionItem->execution?->slaughter_time?->format('H:i') ?? '—',
                    'source' => 'execution',
                ];
            })
            ->values();
    }
}
