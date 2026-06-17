<?php

namespace App\Models;

use App\Models\Concerns\DefinesButcherMeatTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ButcherCutType extends Model
{
    use DefinesButcherMeatTypes;

    protected $fillable = [
        'business_id',
        'name',
        'meat_type',
        'expected_yield_pct',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expected_yield_pct' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function cutOutputs(): HasMany
    {
        return $this->hasMany(ButcherCutOutput::class, 'cut_type_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(ButcherProduct::class, 'cut_type_id');
    }
}
