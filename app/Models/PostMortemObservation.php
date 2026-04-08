<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMortemObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_mortem_inspection_id',
        'category',
        'item',
        'value',
        'notes',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(PostMortemInspection::class, 'post_mortem_inspection_id');
    }
}
