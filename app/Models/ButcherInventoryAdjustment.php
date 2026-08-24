<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherInventoryAdjustment extends Model
{
    public const REASON_RECOUNT = 'recount';

    public const REASON_SHRINKAGE = 'shrinkage';

    public const REASON_FOUND_STOCK = 'found_stock';

    public const REASON_DATA_ERROR = 'data_error';

    public const REASON_OTHER = 'other';

    /** @var list<string> */
    public const REASONS = [
        self::REASON_RECOUNT,
        self::REASON_SHRINKAGE,
        self::REASON_FOUND_STOCK,
        self::REASON_DATA_ERROR,
        self::REASON_OTHER,
    ];

    protected $fillable = [
        'business_id',
        'batch_id',
        'weight_change_kg',
        'previous_weight_kg',
        'new_weight_kg',
        'reason',
        'adjusted_at',
        'adjusted_by',
        'stock_count_line_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight_change_kg' => 'decimal:3',
            'previous_weight_kg' => 'decimal:3',
            'new_weight_kg' => 'decimal:3',
            'adjusted_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ButcherInventoryBatch::class, 'batch_id');
    }

    public function adjustedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function stockCountLine(): BelongsTo
    {
        return $this->belongsTo(ButcherStockCountLine::class, 'stock_count_line_id');
    }
}
