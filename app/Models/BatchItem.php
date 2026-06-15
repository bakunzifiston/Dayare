<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One individual animal assigned to a batch from a slaughter execution.
 */
class BatchItem extends Model
{
    use HasFactory;

    protected $table = 'batch_items';

    protected $fillable = [
        'batch_id',
        'slaughter_execution_item_id',
        'animal_intake_item_id',
        'meat_quantity_kg',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'meat_quantity_kg' => 'decimal:2',
        ];
    }

    /**
     * The batch this animal belongs to.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    /**
     * The slaughter execution item this batch line was sourced from.
     */
    public function executionItem(): BelongsTo
    {
        return $this->belongsTo(SlaughterExecutionItem::class, 'slaughter_execution_item_id');
    }

    /**
     * The underlying intake animal record.
     */
    public function intakeItem(): BelongsTo
    {
        return $this->belongsTo(AnimalIntakeItem::class, 'animal_intake_item_id');
    }

    /**
     * Post-mortem inspection outcome for this batch animal (section 5).
     */
    public function postMortemOutcome(): HasOne
    {
        return $this->hasOne(PostMortemInspectionItem::class, 'batch_item_id');
    }

    /**
     * Whether a post-mortem outcome has been recorded for this animal.
     */
    public function hasPostMortemOutcome(): bool
    {
        if ($this->relationLoaded('postMortemOutcome')) {
            return $this->postMortemOutcome !== null;
        }

        return $this->postMortemOutcome()->exists();
    }

    // --- Section 5 ---

    /**
     * True when this animal was approved at post-mortem.
     */
    public function isApprovedAtPostMortem(): bool
    {
        return $this->postMortemOutcome?->outcome === PostMortemInspectionItem::OUTCOME_APPROVED;
    }

    /**
     * True when this animal was condemned at post-mortem.
     */
    public function isCondemnedAtPostMortem(): bool
    {
        return $this->postMortemOutcome?->outcome === PostMortemInspectionItem::OUTCOME_CONDEMNED;
    }
}
