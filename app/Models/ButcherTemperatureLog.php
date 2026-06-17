<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherTemperatureLog extends Model
{
    public const TYPE_FRESH = 'fresh';

    public const TYPE_FROZEN = 'frozen';

    /** @var list<string> */
    public const STORAGE_TYPES = [
        self::TYPE_FRESH,
        self::TYPE_FROZEN,
    ];

    protected $fillable = [
        'business_id',
        'outlet_id',
        'storage_location',
        'storage_type',
        'temperature_celsius',
        'logged_at',
        'logged_by',
        'is_breach',
        'breach_note',
    ];

    protected function casts(): array
    {
        return [
            'temperature_celsius' => 'decimal:2',
            'logged_at' => 'datetime',
            'is_breach' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(ButcherOutlet::class, 'outlet_id');
    }

    public function loggedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
