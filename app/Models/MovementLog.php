<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementLog extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_MOVEMENT_STARTED = 'movement_started';

    public const ACTION_ARRIVED = 'arrived';

    public const ACTION_VERIFIED = 'verified';

    public const ACTION_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const ACTIONS = [
        self::ACTION_CREATED,
        self::ACTION_UPDATED,
        self::ACTION_APPROVED,
        self::ACTION_REJECTED,
        self::ACTION_MOVEMENT_STARTED,
        self::ACTION_ARRIVED,
        self::ACTION_VERIFIED,
        self::ACTION_CANCELLED,
    ];

    protected $fillable = [
        'movement_permit_id',
        'action_type',
        'action_by',
        'action_date',
        'ip_address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'datetime',
        ];
    }

    public function movementPermit(): BelongsTo
    {
        return $this->belongsTo(MovementPermit::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
