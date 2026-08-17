<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRolePermissionOverride extends Model
{
    protected $fillable = [
        'business_id',
        'role',
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
            BusinessUser::forgetResolvedPermissions(
                (int) $override->business_id,
                (string) $override->role
            );
            User::forgetResolvedProcessorPermissions(
                businessId: (int) $override->business_id
            );
        });

        static::deleted(function (self $override): void {
            BusinessUser::forgetResolvedPermissions(
                (int) $override->business_id,
                (string) $override->role
            );
            User::forgetResolvedProcessorPermissions(
                businessId: (int) $override->business_id
            );
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
