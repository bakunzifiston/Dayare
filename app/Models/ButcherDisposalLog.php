<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherDisposalLog extends Model
{
    public const REASON_EXPIRED = 'expired';

    public const REASON_CONTAMINATED = 'contaminated';

    public const REASON_DAMAGED = 'damaged';

    public const REASON_OTHER = 'other';

    /** @var list<string> */
    public const REASONS = [
        self::REASON_EXPIRED,
        self::REASON_CONTAMINATED,
        self::REASON_DAMAGED,
        self::REASON_OTHER,
    ];

    protected $fillable = [
        'business_id',
        'batch_id',
        'weight_disposed_kg',
        'reason',
        'disposed_at',
        'disposed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight_disposed_kg' => 'decimal:3',
            'disposed_at' => 'datetime',
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

    public function disposedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }
}
