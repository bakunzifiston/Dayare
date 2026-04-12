<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Optional visit / observation log. Does not update {@see Livestock} healthy or sick quantities.
 */
class AnimalHealthRecord extends Model
{
    public const CONDITION_HEALTHY = 'healthy';

    public const CONDITION_SICK = 'sick';

    /** @var list<string> */
    public const CONDITIONS = [
        self::CONDITION_HEALTHY,
        self::CONDITION_SICK,
    ];

    protected $fillable = [
        'farm_id',
        'livestock_id',
        'record_date',
        'condition',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }
}
