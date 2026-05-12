<?php

namespace App\Models;

use App\Services\Farmer\LivestockQualityScore;
use App\Support\FarmerAnimalType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Livestock extends Model
{
    use SoftDeletes;

    public const HERD_STATUS_HEALTHY = 'healthy';

    public const HERD_STATUS_MIXED = 'mixed';

    public const HERD_STATUS_SICK = 'sick';

    public const QUALITY_EXCELLENT = 'excellent';

    public const QUALITY_GOOD = 'good';

    public const QUALITY_FAIR = 'fair';

    public const QUALITY_POOR = 'poor';

    /** @var list<string> */
    public const QUALITY_BANDS = [
        self::QUALITY_EXCELLENT,
        self::QUALITY_GOOD,
        self::QUALITY_FAIR,
        self::QUALITY_POOR,
    ];

    public const HEALTH_HEALTHY = 'healthy';

    public const HEALTH_UNDER_OBSERVATION = 'under_observation';

    public const HEALTH_SICK = 'sick';

    /** @var list<string> */
    public const HEALTH_STATUSES = [
        self::HEALTH_HEALTHY,
        self::HEALTH_UNDER_OBSERVATION,
        self::HEALTH_SICK,
    ];

    public const LIFECYCLE_ACTIVE = 'active';

    public const LIFECYCLE_CLOSED = 'closed';

    public const LIFECYCLE_QUARANTINED = 'quarantined';

    /** @var list<string> */
    public const LIFECYCLE_STATUSES = [
        self::LIFECYCLE_ACTIVE,
        self::LIFECYCLE_CLOSED,
        self::LIFECYCLE_QUARANTINED,
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    public const FEEDING_ORGANIC = 'organic';

    public const FEEDING_PASTURE = 'pasture';

    public const FEEDING_MIXED = 'mixed';

    public const FEEDING_GRAIN = 'grain';

    public const FEEDING_OTHER = 'other';

    /** @var list<string> */
    public const FEEDING_TYPES = [
        self::FEEDING_ORGANIC,
        self::FEEDING_PASTURE,
        self::FEEDING_MIXED,
        self::FEEDING_GRAIN,
        self::FEEDING_OTHER,
    ];

    protected $table = 'livestock';

    protected $fillable = [
        'farm_id',
        'livestock_name',
        'livestock_code',
        'type',
        'livestock_type',
        'breed',
        'production_purpose',
        'feeding_type',
        'farming_method',
        'feeding_method',
        'water_source',
        'total_quantity',
        'available_quantity',
        'total_count',
        'male_count',
        'female_count',
        'young_count',
        'base_price',
        'quality_band',
        'healthy_quantity',
        'sick_quantity',
        'acquisition_date',
        'acquisition_source',
        'health_status',
        'lifecycle_status',
        'housing_location',
        'notes',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_quantity' => 'integer',
            'available_quantity' => 'integer',
            'total_count' => 'integer',
            'male_count' => 'integer',
            'female_count' => 'integer',
            'young_count' => 'integer',
            'healthy_quantity' => 'integer',
            'sick_quantity' => 'integer',
            'base_price' => 'decimal:2',
            'acquisition_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $livestock): void {
            if ($livestock->livestock_type !== null && $livestock->livestock_type !== '') {
                $livestock->type = $livestock->livestock_type;
            } elseif ($livestock->type !== null && $livestock->type !== '') {
                $livestock->livestock_type = $livestock->type;
            }

            if ($livestock->total_count !== null) {
                $livestock->total_quantity = (int) $livestock->total_count;
                if ($livestock->available_quantity === null || $livestock->available_quantity > $livestock->total_count) {
                    $livestock->available_quantity = (int) $livestock->total_count;
                }
            } elseif ($livestock->total_quantity !== null) {
                $livestock->total_count = (int) $livestock->total_quantity;
            }

            if ($livestock->feeding_method !== null && $livestock->feeding_method !== '') {
                $livestock->feeding_type = $livestock->feeding_method;
            } elseif ($livestock->feeding_type !== null && $livestock->feeding_type !== '') {
                $livestock->feeding_method = $livestock->feeding_type;
            }
        });
    }

    protected function herdHealthStatus(): Attribute
    {
        return Attribute::get(function (): string {
            $h = (int) $this->healthy_quantity;
            $s = (int) $this->sick_quantity;

            if ($s === 0) {
                return self::HERD_STATUS_HEALTHY;
            }

            if ($h === 0) {
                return self::HERD_STATUS_SICK;
            }

            return self::HERD_STATUS_MIXED;
        });
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function feedingRecords(): HasMany
    {
        return $this->hasMany(FeedingRecord::class);
    }

    public function detail(): HasOne
    {
        return $this->hasOne(LivestockDetail::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(AnimalHealthRecord::class);
    }

    public function movementPermitLinks(): HasMany
    {
        return $this->hasMany(MovementPermitAnimal::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LivestockEvent::class);
    }

    public function healthCertificates(): HasMany
    {
        return $this->hasMany(FarmerHealthCertificate::class);
    }

    public function latestHealthRecord(): HasOne
    {
        return $this->hasOne(AnimalHealthRecord::class)->latestOfMany('record_date');
    }

    /**
     * @param  Collection<int, self>  $livestock
     * @return array{healthy: int, sick: int, unrecorded: int}
     */
    public static function aggregateHealthQuantities(Collection $livestock): array
    {
        $healthy = 0;
        $sick = 0;
        $unrecorded = 0;

        foreach ($livestock as $row) {
            $healthy += (int) $row->healthy_quantity;
            $sick += (int) $row->sick_quantity;
            $sum = (int) $row->healthy_quantity + (int) $row->sick_quantity;
            $total = (int) ($row->total_count ?? $row->total_quantity);
            if ($sum < $total) {
                $unrecorded += $total - $sum;
            }
        }

        return compact('healthy', 'sick', 'unrecorded');
    }

    public static function types(): array
    {
        return FarmerAnimalType::ALL;
    }

    /**
     * @return array{tier: string, points: int, max: int, breakdown: array<string, int>}
     */
    public function qualityScore(): array
    {
        return LivestockQualityScore::evaluate($this);
    }
}
