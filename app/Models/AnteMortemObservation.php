<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnteMortemObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'ante_mortem_inspection_id',
        'animal_intake_item_id',
        'item',
        'value',
        'notes',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(AnteMortemInspection::class, 'ante_mortem_inspection_id');
    }

    public function intakeItem(): BelongsTo
    {
        return $this->belongsTo(AnimalIntakeItem::class, 'animal_intake_item_id');
    }
}
