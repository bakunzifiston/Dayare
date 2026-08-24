<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ButcherStockCount extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'business_id',
        'outlet_id',
        'count_number',
        'status',
        'count_date',
        'counted_by',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'count_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(ButcherOutlet::class, 'outlet_id');
    }

    public function countedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ButcherStockCountLine::class, 'stock_count_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
