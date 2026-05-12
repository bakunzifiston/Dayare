<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedingSchedule extends Model
{
    use SoftDeletes;

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_TWICE_DAILY = 'twice_daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_CUSTOM = 'custom';

    /** @var list<string> */
    public const FREQUENCIES = [
        self::FREQUENCY_DAILY,
        self::FREQUENCY_TWICE_DAILY,
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_CUSTOM,
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'business_id',
        'schedule_name',
        'animal_id',
        'livestock_id',
        'feed_type_id',
        'feeding_time',
        'feeding_frequency',
        'quantity',
        'instructions',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
