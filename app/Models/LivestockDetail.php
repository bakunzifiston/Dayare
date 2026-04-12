<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivestockDetail extends Model
{
    protected $fillable = [
        'livestock_id',
        'age_range',
        'weight_range',
        'notes',
    ];

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }
}
