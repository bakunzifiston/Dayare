<?php

namespace App\Models;

use App\Models\Concerns\DefinesButcherMeatTypes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ButcherInventoryBatch extends Model
{
    use DefinesButcherMeatTypes;

    public const STATUS_IN_STORAGE = 'in_storage';

    public const STATUS_PARTIALLY_USED = 'partially_used';

    public const STATUS_FULLY_USED = 'fully_used';

    public const STATUS_DISPOSED = 'disposed';

    public const STATUS_EXPIRED = 'expired';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_IN_STORAGE,
        self::STATUS_PARTIALLY_USED,
        self::STATUS_FULLY_USED,
        self::STATUS_DISPOSED,
        self::STATUS_EXPIRED,
    ];

    /** @var list<string> */
    public const ACTIVE_STATUSES = [
        self::STATUS_IN_STORAGE,
        self::STATUS_PARTIALLY_USED,
    ];

    protected $fillable = [
        'business_id',
        'delivery_id',
        'outlet_id',
        'batch_number',
        'meat_type',
        'initial_weight_kg',
        'remaining_weight_kg',
        'unit_cost_per_kg',
        'status',
        'received_at',
        'best_before_date',
        'storage_location',
    ];

    protected function casts(): array
    {
        return [
            'initial_weight_kg' => 'decimal:3',
            'remaining_weight_kg' => 'decimal:3',
            'unit_cost_per_kg' => 'decimal:2',
            'received_at' => 'datetime',
            'best_before_date' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(ButcherDelivery::class, 'delivery_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(ButcherOutlet::class, 'outlet_id');
    }

    public function disposalLogs(): HasMany
    {
        return $this->hasMany(ButcherDisposalLog::class, 'batch_id');
    }

    public function cuttingSessions(): HasMany
    {
        return $this->hasMany(ButcherCuttingSession::class, 'batch_id');
    }

    public function ageInDays(): int
    {
        return (int) Carbon::parse($this->received_at)->startOfDay()->diffInDays(now()->startOfDay());
    }

    public function daysUntilBestBefore(): int
    {
        if ($this->best_before_date === null) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($this->best_before_date->startOfDay(), false);
    }

    public function isExpiringSoon(): bool
    {
        $days = $this->daysUntilBestBefore();

        return $days >= 0 && $days <= 1;
    }

    public function isExpired(): bool
    {
        return $this->best_before_date !== null && $this->best_before_date->isPast();
    }
}
