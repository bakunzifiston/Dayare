<?php

namespace App\Models;

use App\Models\Concerns\DefinesButcherMeatTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ButcherDelivery extends Model
{
    use DefinesButcherMeatTypes;

    public const CONDITION_GOOD = 'good';

    public const CONDITION_FAIR = 'fair';

    public const CONDITION_REJECTED = 'rejected';

    /** @var list<string> */
    public const CONDITIONS = [
        self::CONDITION_GOOD,
        self::CONDITION_FAIR,
        self::CONDITION_REJECTED,
    ];

    protected $fillable = [
        'business_id',
        'purchase_order_id',
        'supplier_id',
        'delivery_number',
        'meat_type',
        'received_weight_kg',
        'unit_cost_per_kg',
        'total_cost',
        // Deprecated: prefer line outcomes. Kept for historical reads / summary queries.
        'condition',
        'received_at',
        'received_by',
        'outlet_id',
        'certificate_ref',
        'certificate_issuer',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_weight_kg' => 'decimal:3',
            'unit_cost_per_kg' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(ButcherPurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ButcherSupplier::class, 'supplier_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(ButcherOutlet::class, 'outlet_id');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function inventoryBatch(): HasOne
    {
        return $this->hasOne(ButcherInventoryBatch::class, 'delivery_id')->latestOfMany();
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(ButcherInventoryBatch::class, 'delivery_id');
    }

    public function rejection(): HasOne
    {
        return $this->hasOne(ButcherDeliveryRejection::class, 'delivery_id')->latestOfMany();
    }

    public function rejections(): HasMany
    {
        return $this->hasMany(ButcherDeliveryRejection::class, 'delivery_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ButcherDeliveryLine::class, 'delivery_id');
    }

    public function createsInventory(): bool
    {
        if ($this->relationLoaded('lines') && $this->lines->isNotEmpty()) {
            return $this->lines->contains(fn (ButcherDeliveryLine $line) => $line->createsInventory());
        }

        return in_array($this->condition, [self::CONDITION_GOOD, self::CONDITION_FAIR], true);
    }
}
