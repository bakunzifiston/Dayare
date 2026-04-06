<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColdRoomViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'cold_room_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public function coldRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
