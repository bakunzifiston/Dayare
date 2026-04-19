<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsOrder extends Model
{
    protected $table = 'logistics_orders';

    public const SERVICE_TYPE_LOCAL = 'local';

    public const SERVICE_TYPE_EXPORT = 'export';

    public const SERVICE_TYPES = [self::SERVICE_TYPE_LOCAL, self::SERVICE_TYPE_EXPORT];

    public const TRANSPORT_MODE_ROAD = 'road';

    public const TRANSPORT_MODE_AIR = 'air';

    public const TRANSPORT_MODE_SEA = 'sea';

    public const TRANSPORT_MODES = [self::TRANSPORT_MODE_ROAD, self::TRANSPORT_MODE_AIR, self::TRANSPORT_MODE_SEA];

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_CONFIRMED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'company_id',
        'service_type',
        'transport_mode',
        'status',
        'pickup_location',
        'delivery_location',
        'total_weight',
        'total_volume',
        'special_instructions',
    ];

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:3',
            'total_volume' => 'decimal:3',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LogisticsOrder $order): void {
            if ($order->order_number !== null && $order->order_number !== '') {
                return;
            }
            do {
                $order->order_number = 'LP-'.strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            } while (static::query()->where('order_number', $order->order_number)->exists());
        });
    }

    /** Integer kg cap for trip allocation (whole kg vs vehicle pivot). */
    public function allocatableWeightKg(): int
    {
        return max(0, (int) round((float) $this->total_weight));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'company_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(LogisticsTrip::class, 'order_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(LogisticsInvoice::class, 'order_id');
    }
}
