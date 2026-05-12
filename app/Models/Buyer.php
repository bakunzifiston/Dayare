<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Buyer extends Model
{
    use SoftDeletes;

    public const TYPE_INDIVIDUAL = 'individual_buyer';

    public const TYPE_FARM = 'farm';

    public const TYPE_MARKET_TRADER = 'market_trader';

    public const TYPE_BUTCHERY = 'butchery';

    public const TYPE_SLAUGHTERHOUSE = 'slaughterhouse';

    public const TYPE_EXPORTER = 'exporter';

    public const TYPE_DISTRIBUTOR = 'distributor';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_INDIVIDUAL,
        self::TYPE_FARM,
        self::TYPE_MARKET_TRADER,
        self::TYPE_BUTCHERY,
        self::TYPE_SLAUGHTERHOUSE,
        self::TYPE_EXPORTER,
        self::TYPE_DISTRIBUTOR,
    ];

    public const TRUST_VERIFIED = 'verified';

    public const TRUST_REGULAR = 'regular';

    public const TRUST_NEW = 'new_buyer';

    public const TRUST_RESTRICTED = 'restricted';

    /** @var list<string> */
    public const TRUST_LEVELS = [
        self::TRUST_VERIFIED,
        self::TRUST_REGULAR,
        self::TRUST_NEW,
        self::TRUST_RESTRICTED,
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_BLACKLISTED = 'blacklisted';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_BLACKLISTED,
    ];

    protected $fillable = [
        'business_id',
        'buyer_code',
        'buyer_name',
        'buyer_type',
        'contact_person',
        'phone',
        'email',
        'national_id',
        'company_registration',
        'country',
        'province',
        'district',
        'address',
        'preferred_payment_method',
        'trust_level',
        'notes',
        'status',
        'created_by',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
