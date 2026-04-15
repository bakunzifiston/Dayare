<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsTrackingLog extends Model
{
    protected $table = 'logistics_tracking_logs';
    public const STATUS_LOADING = 'loading';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_DELAYED = 'delayed';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUSES = [
        self::STATUS_LOADING,
        self::STATUS_IN_TRANSIT,
        self::STATUS_DELAYED,
        self::STATUS_DELIVERED,
        self::STATUS_FAILED,
    ];
    protected $fillable = ['trip_id', 'timestamp', 'location', 'status', 'notes'];

    protected function casts(): array
    {
        return ['timestamp' => 'datetime'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(LogisticsTrip::class, 'trip_id');
    }
}

