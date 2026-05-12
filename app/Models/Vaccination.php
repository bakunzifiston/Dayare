<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Vaccination extends Model
{
    use SoftDeletes;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_MISSED = 'missed';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_COMPLETED,
        self::STATUS_MISSED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'animal_id',
        'vaccination_code',
        'vaccine_name',
        'vaccine_type',
        'manufacturer',
        'batch_number',
        'dosage',
        'administration_method',
        'vaccination_date',
        'next_due_date',
        'veterinarian_name',
        'veterinary_clinic',
        'administered_by',
        'status',
        'side_effects',
        'reaction_notes',
        'attachment_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'vaccination_date' => 'date',
            'next_due_date' => 'date',
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
