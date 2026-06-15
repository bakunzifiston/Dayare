<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PostMortemObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_mortem_inspection_id',
        'animal_intake_item_id',
        'category',
        'item',
        'value',
        'notes',
    ];

    public function intakeItem(): BelongsTo
    {
        return $this->belongsTo(AnimalIntakeItem::class, 'animal_intake_item_id');
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(PostMortemInspection::class, 'post_mortem_inspection_id');
    }
}
