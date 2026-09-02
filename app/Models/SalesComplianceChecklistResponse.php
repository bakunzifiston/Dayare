<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesComplianceChecklistResponse extends Model
{
    protected $fillable = [
        'inspection_id',
        'item_key',
        'result',
        'notes',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(SalesComplianceInspection::class, 'inspection_id');
    }
}
