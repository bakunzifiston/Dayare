<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class VeterinaryVisit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'animal_id',
        'visit_code',
        'visit_date',
        'veterinarian_name',
        'clinic_name',
        'purpose_of_visit',
        'findings',
        'recommendations',
        'follow_up_required',
        'follow_up_date',
        'attachment_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'follow_up_required' => 'boolean',
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
