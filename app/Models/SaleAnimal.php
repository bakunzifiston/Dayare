<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleAnimal extends Model
{
    public const CONDITION_HEALTHY = 'healthy';

    public const CONDITION_GOOD = 'good';

    public const CONDITION_UNDER_OBSERVATION = 'under_observation';

    public const CONDITION_INJURED = 'injured';

    /** @var list<string> */
    public const CONDITIONS = [
        self::CONDITION_HEALTHY,
        self::CONDITION_GOOD,
        self::CONDITION_UNDER_OBSERVATION,
        self::CONDITION_INJURED,
    ];

    protected $fillable = [
        'sale_id',
        'animal_id',
        'livestock_id',
        'sale_price',
        'live_weight',
        'price_per_kg',
        'animal_condition',
        'certificate_verified',
        'movement_permit_verified',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'float',
            'live_weight' => 'float',
            'price_per_kg' => 'float',
            'certificate_verified' => 'boolean',
            'movement_permit_verified' => 'boolean',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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
