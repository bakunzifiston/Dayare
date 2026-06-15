<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnteMortemInspectionItem extends Model
{
    use HasFactory;

    public const OUTCOME_APPROVED = 'approved';

    public const OUTCOME_REJECTED = 'rejected';

    public const OUTCOME_DEFERRED = 'deferred';

    protected $fillable = [
        'ante_mortem_inspection_id',
        'animal_intake_item_id',
        'outcome',
        'outcome_notes',
    ];

    /**
     * Parent ante-mortem inspection record.
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(AnteMortemInspection::class, 'ante_mortem_inspection_id');
    }

    /**
     * Intake animal this outcome applies to.
     */
    public function intakeItem(): BelongsTo
    {
        return $this->belongsTo(AnimalIntakeItem::class, 'animal_intake_item_id');
    }

    /**
     * @param  Builder<AnteMortemInspectionItem>  $query
     * @return Builder<AnteMortemInspectionItem>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('outcome', self::OUTCOME_APPROVED);
    }

    /**
     * @param  Builder<AnteMortemInspectionItem>  $query
     * @return Builder<AnteMortemInspectionItem>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('outcome', self::OUTCOME_REJECTED);
    }

    /**
     * @param  Builder<AnteMortemInspectionItem>  $query
     * @return Builder<AnteMortemInspectionItem>
     */
    public function scopeDeferred(Builder $query): Builder
    {
        return $query->where('outcome', self::OUTCOME_DEFERRED);
    }
}
