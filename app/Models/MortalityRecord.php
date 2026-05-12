<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MortalityRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'animal_id',
        'mortality_code',
        'death_date',
        'cause_of_death',
        'reported_by',
        'postmortem_done',
        'veterinarian_name',
        'disposal_method',
        'notes',
        'attachment_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'death_date' => 'date',
            'postmortem_done' => 'boolean',
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
