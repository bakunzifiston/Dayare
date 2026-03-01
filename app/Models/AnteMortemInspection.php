<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnteMortemInspection extends Model
{
    use HasFactory;

    protected $table = 'ante_mortem_inspections';

    protected $fillable = [
        'slaughter_plan_id',
        'inspector_id',
        'species',
        'number_examined',
        'number_approved',
        'number_rejected',
        'notes',
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

    /**
     * Approved + rejected should not exceed examined.
     */
    public function isValidCounts(): bool
    {
        return ($this->number_approved + $this->number_rejected) <= $this->number_examined;
    }
}
