<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Slaughter Execution – record of actual slaughter for a session.
 * SlaughterSession (1) → Many SlaughterExecution.
 */
class SlaughterExecution extends Model
{
    use HasFactory;

    protected $table = 'slaughter_executions';

    protected $fillable = [
        'slaughter_plan_id',
        'actual_animals_slaughtered',
        'slaughter_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'slaughter_time' => 'datetime',
        ];
    }

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /** Belongs to one SlaughterSession */
    public function slaughterPlan(): BelongsTo
    {
        return $this->belongsTo(SlaughterPlan::class);
    }

    /** SlaughterExecution (1) → Many Batches */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
