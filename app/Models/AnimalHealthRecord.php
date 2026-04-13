<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Optional visit / observation log. Does not update {@see Livestock} healthy or sick quantities.
 */
class AnimalHealthRecord extends Model
{
    public const EVENT_VACCINATION = 'vaccination';

    public const EVENT_TREATMENT = 'treatment';

    public const EVENT_DISEASE_DIAGNOSIS = 'disease_diagnosis';

    /** @var list<string> */
    public const EVENT_TYPES = [
        self::EVENT_VACCINATION,
        self::EVENT_TREATMENT,
        self::EVENT_DISEASE_DIAGNOSIS,
    ];

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
        'batch_reference',
        'record_date',
        'event_type',
        'condition',
        'next_due_date',
        'notes',
        'treatment_given',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'next_due_date' => 'date',
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
