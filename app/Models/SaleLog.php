<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLog extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_PAYMENT_RECORDED = 'payment_recorded';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_COMPLETED = 'completed';

    public const ACTION_CANCELLED = 'cancelled';

    public const ACTION_REFUNDED = 'refunded';

    public const ACTION_DOCUMENT_GENERATED = 'document_generated';

    public const ACTION_DOCUMENT_DOWNLOADED = 'document_downloaded';

    /** @var list<string> */
    public const ACTIONS = [
        self::ACTION_CREATED,
        self::ACTION_UPDATED,
        self::ACTION_PAYMENT_RECORDED,
        self::ACTION_APPROVED,
        self::ACTION_COMPLETED,
        self::ACTION_CANCELLED,
        self::ACTION_REFUNDED,
        self::ACTION_DOCUMENT_GENERATED,
        self::ACTION_DOCUMENT_DOWNLOADED,
    ];

    protected $fillable = [
        'sale_id',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
