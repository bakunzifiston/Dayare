<?php

namespace App\Models;

use App\Services\Farmer\LivestockQualityScore;
use App\Support\FarmerAnimalType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Livestock extends Model
{
    protected $table = 'livestock';

    /** Computed herd status (not stored): all healthy animals */
    public const HERD_STATUS_HEALTHY = 'healthy';

    /** Mixed healthy and sick */
    public const HERD_STATUS_MIXED = 'mixed';

    /** All sick (no healthy) */
    public const HERD_STATUS_SICK = 'sick';

    public const HEALTH_EXCELLENT = 'excellent';

    public const HEALTH_GOOD = 'good';

    public const HEALTH_FAIR = 'fair';

    public const HEALTH_POOR = 'poor';

    /** @var list<string> */
    public const HEALTH_STATUSES = [
        self::HEALTH_EXCELLENT,
        self::HEALTH_GOOD,
        self::HEALTH_FAIR,
        self::HEALTH_POOR,
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

    protected $fillable = [
        'farm_id',
        'type',
        'breed',
        'feeding_type',
        'total_quantity',
        'available_quantity',
        'base_price',
        'health_status',
        'healthy_quantity',
        'sick_quantity',
    ];

    protected function casts(): array
    {
        return [
            'total_quantity' => 'integer',
            'available_quantity' => 'integer',
            'base_price' => 'decimal:2',
            'healthy_quantity' => 'integer',
            'sick_quantity' => 'integer',
        ];
    }

    /**
     * Derived from healthy_quantity / sick_quantity (not persisted).
     * healthy → sick_quantity === 0; sick → healthy_quantity === 0; else mixed.
     */
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

    /**
     * Most recent health log row (does not drive counts).
     */
    public function latestHealthRecord(): HasOne
    {
        return $this->hasOne(AnimalHealthRecord::class)->latestOfMany('record_date');
    }

    /**
     * Sum healthy_quantity / sick_quantity across rows. "unrecorded" = rows where split &lt; total.
     *
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
            $total = (int) $row->total_quantity;
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
