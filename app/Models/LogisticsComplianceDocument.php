<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsComplianceDocument extends Model
{
    protected $table = 'logistics_compliance_documents';
    public const TYPE_HEALTH_CERTIFICATE = 'health_certificate';
    public const TYPE_MOVEMENT_PERMIT = 'movement_permit';
    public const TYPES = [self::TYPE_HEALTH_CERTIFICATE, self::TYPE_MOVEMENT_PERMIT];
    public const STATUS_VALID = 'valid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';
    public const STATUSES = [self::STATUS_VALID, self::STATUS_EXPIRED, self::STATUS_PENDING];
    protected $fillable = ['trip_id', 'type', 'reference_id', 'status'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(LogisticsTrip::class, 'trip_id');
    }
}

