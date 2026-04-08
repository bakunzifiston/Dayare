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
}
