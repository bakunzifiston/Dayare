<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancePayableLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'payable_id',
        'batch_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    public function payable(): BelongsTo
    {
        return $this->belongsTo(FinancePayable::class, 'payable_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
