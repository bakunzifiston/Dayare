<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ButcherCutOutput extends Model
{
    protected $fillable = [
        'business_id',
        'session_id',
        'cut_type_id',
        'weight_kg',
        'remaining_weight_kg',
        'unit_cost_per_kg',
        'label_printed',
        'label_path',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:3',
            'remaining_weight_kg' => 'decimal:3',
            'unit_cost_per_kg' => 'decimal:2',
            'label_printed' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ButcherCuttingSession::class, 'session_id');
    }

    public function cutType(): BelongsTo
    {
        return $this->belongsTo(ButcherCutType::class, 'cut_type_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(ButcherSaleItem::class, 'cut_output_id');
    }

    public function labelUrl(): ?string
    {
        if ($this->label_path === null || $this->label_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->label_path);
    }
}
