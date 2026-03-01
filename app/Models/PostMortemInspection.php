<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Post-Mortem Inspection – one per batch.
 * Batch (1) → One Post-Mortem Inspection.
 */
class PostMortemInspection extends Model
{
    use HasFactory;

    protected $table = 'post_mortem_inspections';

    protected $fillable = [
        'batch_id',
        'inspector_id',
        'total_examined',
        'approved_quantity',
        'condemned_quantity',
        'notes',
        'inspection_date',
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
}
