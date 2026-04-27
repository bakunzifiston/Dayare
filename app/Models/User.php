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
        'super_admin_permissions',
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
            'super_admin_permissions' => 'array',
        ];
    }

    public const SUPER_ADMIN_MODULE_DASHBOARD = 'dashboard';
    public const SUPER_ADMIN_MODULE_VIBE_PROGRAMME = 'vibe_programme';
    public const SUPER_ADMIN_MODULE_CONFIGURATION = 'configuration';
    public const SUPER_ADMIN_MODULE_USER_MANAGEMENT = 'user_management';
    public const SUPER_ADMIN_MODULE_SYSTEM_SETTINGS = 'system_settings';

    /**
     * @return list<string>
     */
    public static function superAdminModules(): array
    {
        return [
            self::SUPER_ADMIN_MODULE_DASHBOARD,
            self::SUPER_ADMIN_MODULE_VIBE_PROGRAMME,
            self::SUPER_ADMIN_MODULE_CONFIGURATION,
            self::SUPER_ADMIN_MODULE_USER_MANAGEMENT,
            self::SUPER_ADMIN_MODULE_SYSTEM_SETTINGS,
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

    /**
     * @return list<string>
     */
    public function normalizedSuperAdminPermissions(): array
    {
        $permissions = $this->super_admin_permissions;
        if (! is_array($permissions)) {
            return [];
        }

        return collect($permissions)
            ->map(fn ($permission) => is_string($permission) ? trim($permission) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function hasSuperAdminModuleAccess(string $module): bool
    {
        if (! $this->isSuperAdmin()) {
            return false;
        }

        $permissions = $this->normalizedSuperAdminPermissions();
        if ($permissions === []) {
            // Backward compatibility: existing super admins without explicit assignment keep full access.
            return true;
        }

        return in_array($module, $permissions, true);
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function operatorManager(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OperatorManager::class, 'email', 'email');
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
        $ids = $owned->merge($member)->unique()->values();

        $routeName = request()?->route()?->getName();
        $isProcessorRoute = $routeName !== null
            && ! str_starts_with($routeName, 'farmer.')
            && ! str_starts_with($routeName, 'logistics.')
            && ! str_starts_with($routeName, 'super-admin.')
            && ! str_starts_with($routeName, 'profile.');
        $activeProcessorBusinessId = session('active_processor_business_id');

        if ($isProcessorRoute
            && $activeProcessorBusinessId !== null
            && $ids->contains((int) $activeProcessorBusinessId)) {
            return collect([(int) $activeProcessorBusinessId]);
        }

        return $ids;
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
        $ownedProcessorIds = Business::query()
            ->where('type', Business::TYPE_PROCESSOR)
            ->where('user_id', $this->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
        if ($ownedProcessorIds->isNotEmpty()) {
            return $ownedProcessorIds;
        }

        $ids = Business::query()
            ->where('type', Business::TYPE_PROCESSOR)
            ->whereIn('id', $this->accessibleBusinessIds())
            ->pluck('id')
            ->values();

        $active = $this->activeProcessorBusinessId();
        if ($active !== null && $ids->contains($active)) {
            return collect([$active]);
        }

        return $ids;
    }

    public function activeProcessorBusinessId(): ?int
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        $processorIds = Business::query()
            ->where('type', Business::TYPE_PROCESSOR)
            ->whereIn('id', $this->businesses()->pluck('id')->merge($this->memberBusinesses()->pluck('businesses.id'))->unique())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
        if ($processorIds->isEmpty()) {
            return null;
        }

        $sessionBusinessId = session('active_processor_business_id');
        if ($sessionBusinessId !== null && $processorIds->contains((int) $sessionBusinessId)) {
            return (int) $sessionBusinessId;
        }

        return (int) $processorIds->first();
    }

    public function setActiveProcessorBusinessId(int $businessId): void
    {
        session(['active_processor_business_id' => $businessId]);
    }

    public function processorRoleForBusiness(?int $businessId = null): ?string
    {
        $targetBusinessId = $businessId ?? $this->activeProcessorBusinessId();
        if ($targetBusinessId === null) {
            return null;
        }

        $membershipRole = BusinessUser::query()
            ->where('user_id', $this->id)
            ->where('business_id', $targetBusinessId)
            ->value('role');
        if ($membershipRole !== null) {
            return $membershipRole;
        }

        $ownsBusiness = Business::query()
            ->whereKey($targetBusinessId)
            ->where('user_id', $this->id)
            ->exists();

        if ($ownsBusiness) {
            $business = Business::find($targetBusinessId);

            return match ($business?->type) {
                Business::TYPE_FARMER => BusinessUser::ROLE_FARMER,
                Business::TYPE_LOGISTICS => BusinessUser::ROLE_LOGISTICS_MANAGER,
                default => BusinessUser::ROLE_ORG_ADMIN,
            };
        }

        return null;
    }

    public function canProcessorPermission(string $permission, ?int $businessId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $targetBusinessId = $businessId ?? $this->activeProcessorBusinessId();
        if ($targetBusinessId !== null && $this->ownsBusiness($targetBusinessId)) {
            return true;
        }

        $role = $this->processorRoleForBusiness($targetBusinessId);

        return BusinessUser::roleHasPermission($role, $permission);
    }

    public function ownsBusiness(int $businessId): bool
    {
        return Business::query()
            ->whereKey($businessId)
            ->where('user_id', $this->id)
            ->exists();
    }

    /**
     * Active species configured for the supplied businesses (or all accessible businesses by default).
     */
    public function configuredSpeciesForBusinessIds(null|array|Collection $businessIds = null): Collection
    {
        if ($this->isSuperAdmin()) {
            return Species::active()->get();
        }

        $ids = $businessIds instanceof Collection
            ? $businessIds->values()
            : collect($businessIds ?? $this->accessibleBusinessIds())->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Species::query()
            ->where('species.is_active', true)
            ->whereHas('businesses', fn ($q) => $q->whereIn('businesses.id', $ids))
            ->orderBy('species.sort_order')
            ->orderBy('species.name')
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Active units configured for the supplied businesses (or all accessible businesses by default).
     */
    public function configuredUnitsForBusinessIds(null|array|Collection $businessIds = null): Collection
    {
        if ($this->isSuperAdmin()) {
            return Unit::active()->get();
        }

        $ids = $businessIds instanceof Collection
            ? $businessIds->values()
            : collect($businessIds ?? $this->accessibleBusinessIds())->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Unit::query()
            ->where('units.is_active', true)
            ->whereHas('businesses', fn ($q) => $q->whereIn('businesses.id', $ids))
            ->orderBy('units.sort_order')
            ->orderBy('units.name')
            ->get()
            ->unique('id')
            ->values();
    }

    public function configuredSpeciesNames(null|array|Collection $businessIds = null): Collection
    {
        return $this->configuredSpeciesForBusinessIds($businessIds)->pluck('name')->values();
    }

    /** Whether this user can manage users in the active processor business. */
    public function canManageTenantUsers(): bool
    {
        return $this->canProcessorPermission(BusinessUser::PERMISSION_MANAGE_BUSINESS_USERS);
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

    /**
     * Mobile/API-facing role label used by clients for feature gating.
     *
     * @deprecated Prefer {@see self::mobileApiWorkspaceContext()} for API responses; kept for legacy callers.
     */
    public function mobileUserRole(): string
    {
        $ctx = $this->mobileApiWorkspaceContext();

        return match ($ctx['userRole']) {
            'super_admin' => 'super_admin',
            BusinessUser::ROLE_ORG_ADMIN => 'business_org_admin',
            BusinessUser::ROLE_OPERATIONS_MANAGER => 'business_operations_manager',
            BusinessUser::ROLE_COMPLIANCE_OFFICER => 'business_compliance_officer',
            BusinessUser::ROLE_INSPECTOR => 'business_inspector',
            BusinessUser::ROLE_LOGISTICS_MANAGER => 'business_logistics_manager',
            BusinessUser::ROLE_AUDITOR => 'business_auditor',
            BusinessUser::ROLE_DRIVER => 'business_driver',
            BusinessUser::ROLE_BUYER => 'business_buyer',
            BusinessUser::ROLE_FARMER => 'business_farmer',
            BusinessUser::ROLE_PROGRAMME_MANAGER => 'business_programme_manager',
            BusinessUser::ROLE_COLD_ROOM_OPERATOR => 'business_cold_room_operator',
            default => 'user',
        };
    }

    /**
     * Workspace context for mobile JSON API: separates **membership role** (owner/manager/staff) from **tenant type** (farmer/processor/logistics).
     *
     * @return array{
     *   userRole: string,
     *   business_type: string|null,
     *   business_id: int|null,
     *   accessible_businesses: list<array{id: int, name: string, type: string, membership: string}>
     * }
     */
    public function mobileApiWorkspaceContext(?int $preferredBusinessId = null): array
    {
        if ($this->isSuperAdmin()) {
            return [
                'userRole' => 'super_admin',
                'business_type' => Business::TYPE_PROCESSOR,
                'business_id' => null,
                'accessible_businesses' => [],
            ];
        }

        $accessible = [];

        foreach ($this->businesses()->orderBy('id')->get() as $business) {
            $membership = match ($business->type) {
                Business::TYPE_FARMER => BusinessUser::ROLE_FARMER,
                Business::TYPE_LOGISTICS => BusinessUser::ROLE_LOGISTICS_MANAGER,
                default => BusinessUser::ROLE_ORG_ADMIN,
            };

            $accessible[] = [
                'id' => $business->id,
                'name' => $business->business_name,
                'type' => $business->type,
                'membership' => $membership,
            ];
        }

        $ownedIds = $this->businesses()->pluck('id')->all();

        foreach ($this->memberBusinesses()->orderBy('businesses.id')->get() as $business) {
            if (in_array($business->id, $ownedIds, true)) {
                continue;
            }
            $membership = $business->pivot->role ?? BusinessUser::ROLE_OPERATIONS_MANAGER;
            $accessible[] = [
                'id' => $business->id,
                'name' => $business->business_name,
                'type' => $business->type,
                'membership' => $membership,
            ];
        }

        $active = null;
        if ($preferredBusinessId !== null) {
            foreach ($accessible as $row) {
                if ($row['id'] === $preferredBusinessId) {
                    $active = $row;
                    break;
                }
            }
        }
        if ($active === null && $accessible !== []) {
            $active = $accessible[0];
        }

        $userRole = 'user';
        if ($active !== null) {
            $userRole = (string) ($active['membership'] ?? 'user');
        }

        return [
            'userRole' => $userRole,
            'business_type' => $active['type'] ?? null,
            'business_id' => $active['id'] ?? null,
            'accessible_businesses' => $accessible,
        ];
    }

    public function defaultDashboardRouteName(): string
    {
        if ($this->isSuperAdmin()) {
            return 'super-admin.dashboard';
        }

        return match ($this->tenantWorkspaceType()) {
            Business::TYPE_FARMER => 'farmer.dashboard',
            Business::TYPE_LOGISTICS => 'logistics.dashboard.index',
            default => 'dashboard',
        };
    }

    /** Relative URL path for post-login / intended redirects. */
    public function tenantDashboardPath(): string
    {
        return route($this->defaultDashboardRouteName(), absolute: false);
    }
}
