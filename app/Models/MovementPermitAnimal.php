<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementPermitAnimal extends Model
{
    public const CONDITION_HEALTHY = 'healthy';

    public const CONDITION_INJURED = 'injured';

    public const CONDITION_UNDER_OBSERVATION = 'under_observation';

    public const CONDITION_QUARANTINED = 'quarantined';

    /** @var list<string> */
    public const CONDITIONS = [
        self::CONDITION_HEALTHY,
        self::CONDITION_INJURED,
        self::CONDITION_UNDER_OBSERVATION,
        self::CONDITION_QUARANTINED,
    ];

    public const LOADING_LOADED = 'loaded';

    public const LOADING_PENDING = 'pending';

    public const LOADING_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const LOADING_STATUSES = [
        self::LOADING_LOADED,
        self::LOADING_PENDING,
        self::LOADING_CANCELLED,
    ];

    public const ARRIVAL_SAFE = 'arrived_safely';

    public const ARRIVAL_INJURED = 'injured';

    public const ARRIVAL_DEAD = 'dead_on_arrival';

    public const ARRIVAL_MISSING = 'missing';

    /** @var list<string> */
    public const ARRIVAL_STATUSES = [
        self::ARRIVAL_SAFE,
        self::ARRIVAL_INJURED,
        self::ARRIVAL_DEAD,
        self::ARRIVAL_MISSING,
    ];

    protected $fillable = [
        'movement_permit_id',
        'animal_id',
        'livestock_id',
        'animal_identifier',
        'species',
        'breed',
        'sex',
        'age_description',
        'quantity',
        'movement_condition',
        'inspection_notes',
        'loading_status',
        'arrival_status',
        'notes',
    ];

    public function movementPermit(): BelongsTo
    {
        return $this->belongsTo(MovementPermit::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }
}
