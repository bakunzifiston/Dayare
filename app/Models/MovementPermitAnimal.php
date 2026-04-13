<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementPermitAnimal extends Model
{
    protected $fillable = [
        'movement_permit_id',
        'livestock_id',
        'animal_identifier',
        'quantity',
    ];

    public function movementPermit(): BelongsTo
    {
        return $this->belongsTo(MovementPermit::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }
}

