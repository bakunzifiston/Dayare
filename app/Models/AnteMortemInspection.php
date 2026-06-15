<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnteMortemInspection extends Model
{
    use HasFactory;

    // --- Section 2 ---
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_ITEMS = 'from_assigned_items';

    protected $table = 'ante_mortem_inspections';

    protected $fillable = [
        'slaughter_plan_id',
        'inspector_id',
        'species',
        'number_examined',
        'number_approved',
        'number_rejected',
        'notes',
        // --- Section 2 ---
        'examined_count_source',
        'notes_for_under_observation',
        'inspection_date',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
        ];
    }

    /** Ante-mortem record belongs to one SlaughterSession */
    public function slaughterPlan(): BelongsTo
    {
        return $this->belongsTo(SlaughterPlan::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Inspector::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(AnteMortemObservation::class);
    }

    /**
     * Approved + rejected should not exceed examined.
     */
    public function isValidCounts(): bool
    {
        return ($this->number_approved + $this->number_rejected) <= $this->number_examined;
    }

    // --- Section 2 ---

    /**
     * Per-animal inspection outcomes linked to this inspection.
     */
    public function inspectionItems(): HasMany
    {
        return $this->hasMany(AnteMortemInspectionItem::class, 'ante_mortem_inspection_id');
    }

    /**
     * True when at least one per-animal outcome has been recorded.
     */
    public function hasPerAnimalOutcomes(): bool
    {
        return $this->inspectionItems()->exists();
    }

    /**
     * Count of animals with any outcome recorded.
     */
    public function getExaminedFromItemsAttribute(): int
    {
        return $this->inspectionItems()->count();
    }

    /**
     * Count of animals with outcome = approved.
     */
    public function getApprovedFromItemsAttribute(): int
    {
        return $this->inspectionItems()->approved()->count();
    }

    /**
     * Count of animals with outcome = rejected.
     */
    public function getRejectedFromItemsAttribute(): int
    {
        return $this->inspectionItems()->rejected()->count();
    }
}
