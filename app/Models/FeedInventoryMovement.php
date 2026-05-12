<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedInventoryMovement extends Model
{
    public const TYPE_RECEIVED = 'received';

    public const TYPE_FEEDING = 'feeding';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_WASTAGE = 'wastage';

    public const TYPE_EXPIRED = 'expired';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_RECEIVED,
        self::TYPE_FEEDING,
        self::TYPE_ADJUSTMENT,
        self::TYPE_WASTAGE,
        self::TYPE_EXPIRED,
    ];

    protected $fillable = [
        'feed_inventory_id',
        'movement_type',
        'quantity_change',
        'balance_after',
        'feeding_record_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'float',
            'balance_after' => 'float',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(FeedInventory::class, 'feed_inventory_id');
    }

    public function feedingRecord(): BelongsTo
    {
        return $this->belongsTo(FeedingRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
