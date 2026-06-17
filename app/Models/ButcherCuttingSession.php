<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ButcherCuttingSession extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_CLOSED,
    ];

    protected $fillable = [
        'business_id',
        'outlet_id',
        'batch_id',
        'session_number',
        'source_weight_kg',
        'total_cuts_weight_kg',
        'wastage_kg',
        'wastage_pct',
        'session_date',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'source_weight_kg' => 'decimal:3',
            'total_cuts_weight_kg' => 'decimal:3',
            'wastage_kg' => 'decimal:3',
            'wastage_pct' => 'decimal:2',
            'session_date' => 'date',
            'closed_at' => 'datetime',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ButcherInventoryBatch::class, 'batch_id');
    }

    public function cutOutputs(): HasMany
    {
        return $this->hasMany(ButcherCutOutput::class, 'session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
