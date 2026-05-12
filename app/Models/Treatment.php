<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Treatment extends Model
{
    use SoftDeletes;

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_FOLLOW_UP_NEEDED = 'follow_up_needed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ONGOING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_FOLLOW_UP_NEEDED,
    ];

    protected $fillable = [
        'animal_id',
        'treatment_code',
        'disease_name',
        'symptoms',
        'diagnosis',
        'medicine_name',
        'dosage',
        'treatment_method',
        'treatment_start_date',
        'treatment_end_date',
        'veterinarian_name',
        'response_to_treatment',
        'follow_up_date',
        'status',
        'notes',
        'attachment_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'treatment_start_date' => 'date',
            'treatment_end_date' => 'date',
            'follow_up_date' => 'date',
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
