<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedingRecord extends Model
{
    use SoftDeletes;

    public const METHOD_MANUAL = 'manual';

    public const METHOD_AUTOMATIC = 'automatic';

    public const METHOD_GRAZING = 'grazing';

    public const METHOD_BOTTLE = 'bottle_feeding';

    /** @var list<string> */
    public const METHODS = [
        self::METHOD_MANUAL,
        self::METHOD_AUTOMATIC,
        self::METHOD_GRAZING,
        self::METHOD_BOTTLE,
    ];

    public const APPETITE_GOOD = 'good';

    public const APPETITE_NORMAL = 'normal';

    public const APPETITE_POOR = 'poor';

    public const APPETITE_REFUSED = 'refused_feed';

    /** @var list<string> */
    public const APPETITE_STATUSES = [
        self::APPETITE_GOOD,
        self::APPETITE_NORMAL,
        self::APPETITE_POOR,
        self::APPETITE_REFUSED,
    ];

    protected $fillable = [
        'feeding_code',
        'animal_id',
        'livestock_id',
        'feed_type_id',
        'feed_inventory_id',
        'quantity',
        'feeding_method',
        'feeding_time',
        'feeding_date',
        'fed_by',
        'appetite_status',
        'water_provided',
        'feeding_response',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'feeding_date' => 'date',
            'water_provided' => 'boolean',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }

    public function feedType(): BelongsTo
    {
        return $this->belongsTo(FeedType::class);
    }

    public function feedInventory(): BelongsTo
    {
        return $this->belongsTo(FeedInventory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
