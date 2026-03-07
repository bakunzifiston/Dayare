<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'contact_person',
        'email',
        'phone',
        'country',
        'address_line_1',
        'address_line_2',
        'city',
        'state_region',
        'postal_code',
        'tax_id',
        'registration_number',
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

    public function deliveryConfirmations(): HasMany
    {
        return $this->hasMany(DeliveryConfirmation::class);
    }

    /** Single-line address for display (city, country, etc.). */
    public function getAddressLineAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state_region,
            $this->postal_code,
            $this->country,
        ]);
        return implode(', ', $parts) ?: '—';
    }

    /** Display name: name + country for lists. */
    public function getDisplayNameAttribute(): string
    {
        return trim($this->name . ' (' . $this->country . ')');
    }
}
