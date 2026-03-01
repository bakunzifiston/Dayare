<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    /**
     * Boot the trait and add global scope for tenant (current user) isolation.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && static::tenantColumn()) {
                $builder->where(static::tenantColumn(), auth()->id());
            }
        });

        static::creating(function (Model $model) {
            if (auth()->check() && static::tenantColumn() && ! $model->getAttribute(static::tenantColumn())) {
                $model->setAttribute(static::tenantColumn(), auth()->id());
            }
        });
    }

    /**
     * Column name for the tenant (user) foreign key. Override in model if different.
     */
    public static function tenantColumn(): string
    {
        return 'user_id';
    }

    /**
     * Scope to current tenant (logged-in user) only.
     */
    public function scopeForCurrentTenant(Builder $query): Builder
    {
        return $query->where(static::tenantColumn(), auth()->id());
    }

    /**
     * Get the user (tenant) that owns the model.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\User::class, static::tenantColumn());
    }
}
