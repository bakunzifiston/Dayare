<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinanceCostAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'batch_id',
        'source_type',
        'source_id',
        'created_by',
        'category',
        'amount',
        'allocation_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'allocation_date' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
