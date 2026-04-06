<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColdRoomTemperatureLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cold_room_id',
        'temperature',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function coldRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class);
    }
}
