<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMortemCondemnedOrgan extends Model
{
    use HasFactory;

    protected $table = 'post_mortem_condemned_organs';

    protected $fillable = [
        'post_mortem_inspection_item_id',
        'organ_name',
        'weight_kg',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function inspectionItem(): BelongsTo
    {
        return $this->belongsTo(PostMortemInspectionItem::class, 'post_mortem_inspection_item_id');
    }
}
