<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CasualWorker extends Model
{
    protected $fillable = [
        'business_id',
        'first_name',
        'last_name',
        'phone',
        'national_id',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function financePayables(): HasMany
    {
        return $this->hasMany(FinancePayable::class);
    }

    public function displayName(): string
    {
        $name = trim((string) $this->first_name.' '.(string) $this->last_name);

        return $name !== '' ? $name : (string) __('Casual worker #:id', ['id' => $this->id]);
    }
}
