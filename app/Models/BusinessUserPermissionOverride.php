<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessUserPermissionOverride extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'permission',
        'is_allowed',
    ];

    protected function casts(): array
    {
        return [
            'is_allowed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $override): void {
            User::forgetResolvedProcessorPermissions(
                (int) $override->user_id,
                (int) $override->business_id
            );
        });

        static::deleted(function (self $override): void {
            User::forgetResolvedProcessorPermissions(
                (int) $override->user_id,
                (int) $override->business_id
            );
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
