<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Post-Mortem Inspection – one per batch.
 * Batch (1) → One Post-Mortem Inspection.
 */
class PostMortemInspection extends Model
{
    use HasFactory;

    protected $table = 'post_mortem_inspections';

    public const RESULT_APPROVED = 'approved';

    public const RESULT_PARTIAL = 'partial';

    public const RESULT_REJECTED = 'rejected';

    protected $fillable = [
        'batch_id',
        'inspector_id',
        'species',
        'total_examined',
        'approved_quantity',
        'condemned_quantity',
        'notes',
        'inspection_date',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'total_examined' => 'decimal:2',
            'approved_quantity' => 'decimal:2',
            'condemned_quantity' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Inspector::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(PostMortemObservation::class);
    }

    // --- Section 5 ---

    /**
     * Per-animal outcomes recorded during this inspection.
     */
    public function inspectionItems(): HasMany
    {
        return $this->hasMany(PostMortemInspectionItem::class, 'post_mortem_inspection_id');
    }

    /**
     * True when per-animal outcome rows exist for this inspection.
     */
    public function hasPerAnimalOutcomes(): bool
    {
        if ($this->relationLoaded('inspectionItems')) {
            return $this->inspectionItems->isNotEmpty();
        }

        return $this->inspectionItems()->exists();
    }

    /**
     * Count of animals approved at post-mortem (from per-animal items).
     */
    public function getApprovedFromItemsAttribute(): int
    {
        if ($this->relationLoaded('inspectionItems')) {
            return $this->inspectionItems->where('outcome', PostMortemInspectionItem::OUTCOME_APPROVED)->count();
        }

        return $this->inspectionItems()->approved()->count();
    }

    /**
     * Count of animals condemned at post-mortem (from per-animal items).
     */
    public function getCondemnedFromItemsAttribute(): int
    {
        if ($this->relationLoaded('inspectionItems')) {
            return $this->inspectionItems->where('outcome', PostMortemInspectionItem::OUTCOME_CONDEMNED)->count();
        }

        return $this->inspectionItems()->condemned()->count();
    }
}
