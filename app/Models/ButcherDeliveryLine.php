<?php

namespace App\Models;

use App\Models\Concerns\DefinesButcherMeatTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ButcherDeliveryLine extends Model
{
    use DefinesButcherMeatTypes;

    public const OUTCOME_ACCEPTED = 'accepted';

    public const OUTCOME_PARTIALLY_ACCEPTED = 'partially_accepted';

    public const OUTCOME_REJECTED = 'rejected';

    /** @var list<string> */
    public const OUTCOMES = [
        self::OUTCOME_ACCEPTED,
        self::OUTCOME_PARTIALLY_ACCEPTED,
        self::OUTCOME_REJECTED,
    ];

    protected $fillable = [
        'delivery_id',
        'meat_type',
        'expected_weight_kg',
        'received_weight_kg',
        'temperature_c',
        'condition_notes',
        'outcome',
        'accepted_weight_kg',
        'rejected_weight_kg',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'expected_weight_kg' => 'decimal:3',
            'received_weight_kg' => 'decimal:3',
            'temperature_c' => 'decimal:2',
            'accepted_weight_kg' => 'decimal:3',
            'rejected_weight_kg' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(ButcherDelivery::class, 'delivery_id');
    }

    public function inventoryBatch(): HasOne
    {
        return $this->hasOne(ButcherInventoryBatch::class, 'delivery_line_id');
    }

    public function rejection(): HasOne
    {
        return $this->hasOne(ButcherDeliveryRejection::class, 'delivery_line_id');
    }

    public function createsInventory(): bool
    {
        return in_array($this->outcome, [self::OUTCOME_ACCEPTED, self::OUTCOME_PARTIALLY_ACCEPTED], true)
            && (float) $this->accepted_weight_kg > 0;
    }

    public function createsRejection(): bool
    {
        return in_array($this->outcome, [self::OUTCOME_REJECTED, self::OUTCOME_PARTIALLY_ACCEPTED], true)
            && (float) $this->rejected_weight_kg > 0;
    }
}
