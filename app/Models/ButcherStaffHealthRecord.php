<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherStaffHealthRecord extends Model
{
    public const STATUS_FIT = 'fit';

    public const STATUS_RESTRICTED = 'restricted';

    public const STATUS_ON_LEAVE = 'on_leave';

    /** @var list<string> */
    public const HEALTH_STATUSES = [
        self::STATUS_FIT,
        self::STATUS_RESTRICTED,
        self::STATUS_ON_LEAVE,
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'medical_card_number',
        'issued_date',
        'expiry_date',
        'health_status',
        'last_checked_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'expiry_date' => 'date',
            'last_checked_at' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        $days = $this->daysUntilExpiry();

        return $days >= 0 && $days <= $withinDays;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }
}
