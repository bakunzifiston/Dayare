<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessOwnershipMember extends Model
{
    protected $fillable = ['business_id', 'first_name', 'last_name', 'date_of_birth', 'gender', 'pwd_status', 'phone', 'email', 'sort_order'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
