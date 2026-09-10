<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherCuttingSessionSource extends Model
{
    protected $fillable = [
        'session_id',
        'batch_id',
        'source_weight_kg',
    ];

    protected function casts(): array
    {
        return [
            'source_weight_kg' => 'decimal:3',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ButcherCuttingSession::class, 'session_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ButcherInventoryBatch::class, 'batch_id');
    }
}
