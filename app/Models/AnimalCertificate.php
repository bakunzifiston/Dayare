<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AnimalCertificate extends Model
{
    use SoftDeletes;

    public const TYPE_OWNERSHIP = 'ownership';

    public const TYPE_HEALTH = 'health';

    public const TYPE_TRACEABILITY = 'traceability';

    public const TYPE_TRANSFER = 'transfer';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_OWNERSHIP,
        self::TYPE_HEALTH,
        self::TYPE_TRACEABILITY,
        self::TYPE_TRANSFER,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
        self::STATUS_REVOKED,
    ];

    protected $fillable = [
        'animal_id',
        'template_id',
        'certificate_number',
        'certificate_type',
        'certificate_title',
        'issue_date',
        'expiry_date',
        'issued_by',
        'veterinarian_name',
        'verification_token',
        'qr_code',
        'digital_signature',
        'certificate_status',
        'remarks',
        'pdf_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AnimalCertificateTemplate::class, 'template_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AnimalCertificateLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verificationUrl(): string
    {
        return route('animal.verify', ['token' => $this->verification_token]);
    }

    public function pdfUrl(): ?string
    {
        if ($this->pdf_path === null || $this->pdf_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->pdf_path);
    }

    public function syncStatusFromDates(): void
    {
        if ($this->certificate_status === self::STATUS_REVOKED) {
            return;
        }

        if ($this->expiry_date !== null && $this->expiry_date->isPast()) {
            if ($this->certificate_status !== self::STATUS_EXPIRED) {
                $this->forceFill(['certificate_status' => self::STATUS_EXPIRED])->saveQuietly();
            }

            return;
        }

        if ($this->certificate_status === self::STATUS_EXPIRED && ($this->expiry_date === null || $this->expiry_date->isFuture())) {
            $this->forceFill(['certificate_status' => self::STATUS_ACTIVE])->saveQuietly();
        }
    }

    public function isPubliclyValid(): bool
    {
        $this->syncStatusFromDates();

        return $this->certificate_status === self::STATUS_ACTIVE;
    }
}
