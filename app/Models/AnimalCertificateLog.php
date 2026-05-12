<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalCertificateLog extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DOWNLOADED = 'downloaded';

    public const ACTION_VERIFIED = 'verified';

    public const ACTION_REVOKED = 'revoked';

    public const ACTION_EXPIRED = 'expired';

    /** @var list<string> */
    public const ACTIONS = [
        self::ACTION_CREATED,
        self::ACTION_UPDATED,
        self::ACTION_DOWNLOADED,
        self::ACTION_VERIFIED,
        self::ACTION_REVOKED,
        self::ACTION_EXPIRED,
    ];

    protected $fillable = [
        'animal_certificate_id',
        'action_type',
        'action_by',
        'action_date',
        'ip_address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'datetime',
        ];
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(AnimalCertificate::class, 'animal_certificate_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
