<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /** Businesses this user is a member of (not owner), with role on pivot. */
    public function memberBusinesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_user')
            ->withPivot('role')
            ->withTimestamps()
            ->using(BusinessUser::class);
    }

    /** All business IDs the user can access (owned + member). */
    public function accessibleBusinessIds(): Collection
    {
        $owned = $this->businesses()->pluck('id');
        $member = $this->memberBusinesses()->pluck('businesses.id');
        return $owned->merge($member)->unique()->values();
    }

    /** All businesses the user can access (owned + member). */
    public function accessibleBusinesses()
    {
        return Business::whereIn('id', $this->accessibleBusinessIds());
    }

    /** Whether this user is a tenant owner / can manage tenant users. */
    public function canManageTenantUsers(): bool
    {
        // Tenant Owner is defined as a user with the "owner" role OR a user who owns at least one business.
        if ($this->hasRole('owner')) {
            return true;
        }

        return $this->businesses()->exists();
    }
}
