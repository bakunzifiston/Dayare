<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedType extends Model
{
    use SoftDeletes;

    public const CATEGORY_STARTER = 'starter_feed';

    public const CATEGORY_GROWER = 'grower_feed';

    public const CATEGORY_FINISHER = 'finisher_feed';

    public const CATEGORY_LAYER = 'layer_feed';

    public const CATEGORY_DAIRY = 'dairy_feed';

    public const CATEGORY_HAY = 'hay';

    public const CATEGORY_SILAGE = 'silage';

    public const CATEGORY_SUPPLEMENTS = 'supplements';

    public const CATEGORY_MINERALS = 'minerals';

    /** @var list<string> */
    public const CATEGORIES = [
        self::CATEGORY_STARTER,
        self::CATEGORY_GROWER,
        self::CATEGORY_FINISHER,
        self::CATEGORY_LAYER,
        self::CATEGORY_DAIRY,
        self::CATEGORY_HAY,
        self::CATEGORY_SILAGE,
        self::CATEGORY_SUPPLEMENTS,
        self::CATEGORY_MINERALS,
    ];

    public const FORM_MASH = 'mash';

    public const FORM_PELLETS = 'pellets';

    public const FORM_CRUMBLES = 'crumbles';

    public const FORM_LIQUID = 'liquid';

    public const FORM_DRY = 'dry';

    /** @var list<string> */
    public const FORMS = [
        self::FORM_MASH,
        self::FORM_PELLETS,
        self::FORM_CRUMBLES,
        self::FORM_LIQUID,
        self::FORM_DRY,
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
        'feed_name',
        'feed_code',
        'feed_category',
        'feed_form',
        'unit',
        'protein_percentage',
        'energy_value',
        'nutritional_value',
        'manufacturer',
        'description',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'protein_percentage' => 'float',
            'energy_value' => 'float',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(FeedInventory::class);
    }

    public function feedingRecords(): HasMany
    {
        return $this->hasMany(FeedingRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
