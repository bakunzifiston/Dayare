<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnimalOwnershipTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'animal_id',
        'previous_owner',
        'new_owner',
        'transfer_date',
        'transfer_reason',
        'approved_by',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
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
}
