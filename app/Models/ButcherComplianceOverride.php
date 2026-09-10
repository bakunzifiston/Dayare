<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherComplianceOverride extends Model
{
    public const CONTEXT_CUTTING_SESSION = 'cutting_session';

    public const CONTEXT_CUTTING_SOURCE = 'cutting_source';

    public const CONTEXT_SALE = 'sale';

    public const CONTEXT_ORDER_FULFILLMENT = 'order_fulfillment';

    public const ISSUE_TEMPERATURE_BREACH = 'temperature_breach';

    public const ISSUE_EXPIRED = 'expired';

    protected $fillable = [
        'business_id',
        'context_type',
        'context_id',
        'batch_id',
        'cut_output_id',
        'reason',
        'issues',
        'overridden_by',
        'overridden_at',
    ];

    protected function casts(): array
    {
        return [
            'issues' => 'array',
            'overridden_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ButcherInventoryBatch::class, 'batch_id');
    }

    public function cutOutput(): BelongsTo
    {
        return $this->belongsTo(ButcherCutOutput::class, 'cut_output_id');
    }

    public function overriddenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
