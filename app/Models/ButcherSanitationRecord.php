<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherSanitationRecord extends Model
{
    public const TYPE_DAILY_CLEAN = 'daily_clean';

    public const TYPE_DEEP_CLEAN = 'deep_clean';

    public const TYPE_SANITIZE = 'sanitize';

    public const TYPE_INSPECTION = 'inspection';

    /** @var list<string> */
    public const CLEANING_TYPES = [
        self::TYPE_DAILY_CLEAN,
        self::TYPE_DEEP_CLEAN,
        self::TYPE_SANITIZE,
        self::TYPE_INSPECTION,
    ];

    protected $fillable = [
        'business_id',
        'outlet_id',
        'equipment_name',
        'cleaning_type',
        'chemical_used',
        'performed_at',
        'performed_by',
        'next_due_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'next_due_at' => 'datetime',
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

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function isOverdue(): bool
    {
        return $this->next_due_at !== null && $this->next_due_at->isPast();
    }
}
