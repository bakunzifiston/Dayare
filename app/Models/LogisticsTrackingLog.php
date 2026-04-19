<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsTrackingLog extends Model
{
    protected $table = 'logistics_tracking_logs';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_LOADED = 'loaded';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_AT_CHECKPOINT = 'at_checkpoint';

    public const STATUS_DELAYED = 'delayed';

    public const STATUS_ARRIVED = 'arrived';

    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_LOADED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_AT_CHECKPOINT,
        self::STATUS_DELAYED,
        self::STATUS_ARRIVED,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'trip_id',
        'location_id',
        'latitude',
        'longitude',
        'status',
        'event_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(LogisticsTrip::class, 'trip_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
