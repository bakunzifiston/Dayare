<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaughterExecutionItem extends Model
{
    use HasFactory;

    protected $table = 'slaughter_execution_items';

    protected $fillable = [
        'slaughter_execution_id',
        'animal_intake_item_id',
        'meat_quantity_kg',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'meat_quantity_kg' => 'decimal:2',
        ];
    }

    /**
     * The slaughter execution this item belongs to.
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(SlaughterExecution::class, 'slaughter_execution_id');
    }

    /**
     * The individual animal intake item that was slaughtered.
     */
    public function intakeItem(): BelongsTo
    {
        return $this->belongsTo(AnimalIntakeItem::class, 'animal_intake_item_id');
    }

    // --- Section 1 ---

    /**
     * Batch item rows that reference this slaughter execution item.
     */
    public function batchItems(): HasMany
    {
        return $this->hasMany(BatchItem::class, 'slaughter_execution_item_id');
    }

    /**
     * True when this execution item has been assigned to at least one batch.
     */
    public function isInBatch(): bool
    {
        if ($this->relationLoaded('batchItems')) {
            return $this->batchItems->isNotEmpty();
        }

        return $this->batchItems()->exists();
    }
}
