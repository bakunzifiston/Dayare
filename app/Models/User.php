<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        'email_normalized',
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

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->email !== null) {
                $user->email = Str::lower(trim((string) $user->email));
                $user->email_normalized = $user->email;
            }
        });
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

    /** Business IDs the user may act as for farmer workspace (owned or member, type farmer). */
    public function accessibleFarmerBusinessIds(): Collection
    {
        return Business::query()
            ->where('type', Business::TYPE_FARMER)
            ->whereIn('id', $this->accessibleBusinessIds())
            ->pluck('id')
            ->values();
    }

    /** Business IDs for processor workspace modules. */
    public function accessibleProcessorBusinessIds(): Collection
    {
        return Business::query()
            ->where('type', Business::TYPE_PROCESSOR)
            ->whereIn('id', $this->accessibleBusinessIds())
            ->pluck('id')
            ->values();
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

    /**
     * Primary workspace type for routing and access control: first owned business, else first member business, else processor.
     */
    public function tenantWorkspaceType(): string
    {
        if ($this->isSuperAdmin()) {
            return Business::TYPE_PROCESSOR;
        }

        $owned = $this->businesses()->orderBy('id')->first();
        if ($owned !== null) {
            return $owned->type ?? Business::TYPE_PROCESSOR;
        }

        $member = $this->memberBusinesses()->orderBy('businesses.id')->first();
        if ($member !== null) {
            return $member->type ?? Business::TYPE_PROCESSOR;
        }

        return Business::TYPE_PROCESSOR;
    }

    public function defaultDashboardRouteName(): string
    {
        if ($this->isSuperAdmin()) {
            return 'super-admin.dashboard';
        }

        return match ($this->tenantWorkspaceType()) {
            Business::TYPE_FARMER => 'farmer.dashboard',
            Business::TYPE_LOGISTICS => 'logistics.dashboard',
            default => 'dashboard',
        };
    }

    /** Relative URL path for post-login / intended redirects. */
    public function tenantDashboardPath(): string
    {
        return route($this->defaultDashboardRouteName(), absolute: false);
    }
}
