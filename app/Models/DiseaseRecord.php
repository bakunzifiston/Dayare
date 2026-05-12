<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class DiseaseRecord extends Model
{
    use SoftDeletes;

    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    /** @var list<string> */
    public const SEVERITY_LEVELS = [
        self::SEVERITY_LOW,
        self::SEVERITY_MEDIUM,
        self::SEVERITY_HIGH,
        self::SEVERITY_CRITICAL,
    ];

    public const RECOVERY_RECOVERING = 'recovering';

    public const RECOVERY_RECOVERED = 'recovered';

    public const RECOVERY_CHRONIC = 'chronic';

    public const RECOVERY_DEAD = 'dead';

    /** @var list<string> */
    public const RECOVERY_STATUSES = [
        self::RECOVERY_RECOVERING,
        self::RECOVERY_RECOVERED,
        self::RECOVERY_CHRONIC,
        self::RECOVERY_DEAD,
    ];

    public const CONTAGIOUS_UNKNOWN = 'unknown';

    public const CONTAGIOUS_YES = 'contagious';

    public const CONTAGIOUS_NO = 'non_contagious';

    /** @var list<string> */
    public const CONTAGIOUS_STATUSES = [
        self::CONTAGIOUS_UNKNOWN,
        self::CONTAGIOUS_YES,
        self::CONTAGIOUS_NO,
    ];

    protected $fillable = [
        'animal_id',
        'disease_code',
        'disease_name',
        'symptoms',
        'severity_level',
        'diagnosis_date',
        'quarantine_required',
        'contagious_status',
        'recovery_status',
        'veterinarian_name',
        'notes',
        'attachment_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'diagnosis_date' => 'date',
            'quarantine_required' => 'boolean',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachmentUrl(): ?string
    {
        if ($this->attachment_path === null || $this->attachment_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}
