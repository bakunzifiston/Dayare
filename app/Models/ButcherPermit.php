<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ButcherPermit extends Model
{
    public const TYPE_OPERATING_LICENSE = 'operating_license';

    public const TYPE_RFA_PERMIT = 'rfa_permit';

    public const TYPE_HEALTH_CERTIFICATE = 'health_certificate';

    public const TYPE_RICA = 'rica';

    public const TYPE_OTHER = 'other';

    /** @var list<string> */
    public const PERMIT_TYPES = [
        self::TYPE_OPERATING_LICENSE,
        self::TYPE_RFA_PERMIT,
        self::TYPE_HEALTH_CERTIFICATE,
        self::TYPE_RICA,
        self::TYPE_OTHER,
    ];

    public const STATUS_VALID = 'valid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_PENDING_RENEWAL = 'pending_renewal';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_VALID,
        self::STATUS_EXPIRED,
        self::STATUS_PENDING_RENEWAL,
    ];

    protected $fillable = [
        'business_id',
        'permit_type',
        'permit_number',
        'issued_by',
        'issue_date',
        'expiry_date',
        'document_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function documentUrl(): ?string
    {
        if ($this->document_path === null || $this->document_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->document_path);
    }
}
