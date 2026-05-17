<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermitRequestAnimal extends Model
{
    protected $fillable = [
        'permit_request_id',
        'animal_id',
        'livestock_id',
        'animal_identifier',
        'quantity',
        'eligibility_passed',
        'eligibility_issues',
    ];

    protected function casts(): array
    {
        return [
            'eligibility_passed' => 'boolean',
            'eligibility_issues' => 'array',
        ];
    }

    public function permitRequest(): BelongsTo
    {
        return $this->belongsTo(PermitRequest::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }
}
