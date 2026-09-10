<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class ButcherInventoryMovement extends Model
{
    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_CUTTING_CONSUMPTION = 'cutting_consumption';

    public const TYPE_CUTTING_OUTPUT_IN = 'cutting_output_in';

    public const TYPE_SALE = 'sale';

    public const TYPE_WASTE = 'waste';

    public const TYPE_DISPOSAL = 'disposal';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_STOCK_COUNT_VARIANCE = 'stock_count_variance';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_RETURN_IN = 'return_in';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_RECEIPT,
        self::TYPE_CUTTING_CONSUMPTION,
        self::TYPE_CUTTING_OUTPUT_IN,
        self::TYPE_SALE,
        self::TYPE_WASTE,
        self::TYPE_DISPOSAL,
        self::TYPE_ADJUSTMENT,
        self::TYPE_STOCK_COUNT_VARIANCE,
        self::TYPE_TRANSFER_OUT,
        self::TYPE_TRANSFER_IN,
        self::TYPE_RETURN_IN,
    ];

    protected $fillable = [
        'business_id',
        'outlet_id',
        'batch_id',
        'cut_output_id',
        'type',
        'quantity_kg',
        'before_qty',
        'after_qty',
        'reference_type',
        'reference_id',
        'actor_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:3',
            'before_qty' => 'decimal:3',
            'after_qty' => 'decimal:3',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * Write a ledger movement row.
     *
     * @param  array{
     *     business_id: int,
     *     type: string,
     *     quantity_kg: float|int|string,
     *     outlet_id?: int|null,
     *     batch_id?: int|null,
     *     cut_output_id?: int|null,
     *     before_qty?: float|int|string|null,
     *     after_qty?: float|int|string|null,
     *     reference_type?: string|null,
     *     reference_id?: int|null,
     *     actor_id?: int|null,
     *     occurred_at?: Carbon|\DateTimeInterface|string|null
     * }  $attributes
     */
    public static function record(array $attributes): self
    {
        $type = (string) ($attributes['type'] ?? '');
        if (! in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Invalid butcher inventory movement type [{$type}].");
        }

        return self::query()->create([
            'business_id' => (int) $attributes['business_id'],
            'outlet_id' => $attributes['outlet_id'] ?? null,
            'batch_id' => $attributes['batch_id'] ?? null,
            'cut_output_id' => $attributes['cut_output_id'] ?? null,
            'type' => $type,
            'quantity_kg' => $attributes['quantity_kg'],
            'before_qty' => $attributes['before_qty'] ?? null,
            'after_qty' => $attributes['after_qty'] ?? null,
            'reference_type' => $attributes['reference_type'] ?? null,
            'reference_id' => $attributes['reference_id'] ?? null,
            'actor_id' => $attributes['actor_id'] ?? null,
            'occurred_at' => $attributes['occurred_at'] ?? now(),
        ]);
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

    public function cutOutput(): BelongsTo
    {
        return $this->belongsTo(ButcherCutOutput::class, 'cut_output_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
