<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedInventory extends Model
{
    use SoftDeletes;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_LOW_STOCK = 'low_stock';

    public const STATUS_OUT_OF_STOCK = 'out_of_stock';

    public const STATUS_EXPIRED = 'expired';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_LOW_STOCK,
        self::STATUS_OUT_OF_STOCK,
        self::STATUS_EXPIRED,
    ];

    protected $fillable = [
        'feed_type_id',
        'inventory_code',
        'supplier_id',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'total_cost',
        'purchase_date',
        'expiry_date',
        'storage_location',
        'reorder_level',
        'batch_number',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'float',
            'quantity_remaining' => 'float',
            'unit_cost' => 'float',
            'total_cost' => 'float',
            'reorder_level' => 'float',
            'purchase_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function feedType(): BelongsTo
    {
        return $this->belongsTo(FeedType::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(FeedSupplier::class, 'supplier_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FeedInventoryMovement::class);
    }

    public function feedingRecords(): HasMany
    {
        return $this->hasMany(FeedingRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function syncStatus(): void
    {
        $status = self::STATUS_AVAILABLE;

        if ($this->expiry_date !== null && $this->expiry_date->isPast()) {
            $status = self::STATUS_EXPIRED;
        } elseif ((float) $this->quantity_remaining <= 0) {
            $status = self::STATUS_OUT_OF_STOCK;
        } elseif ($this->reorder_level !== null && (float) $this->quantity_remaining <= (float) $this->reorder_level) {
            $status = self::STATUS_LOW_STOCK;
        }

        if ($this->status !== $status) {
            $this->forceFill(['status' => $status])->saveQuietly();
        }
    }
}
