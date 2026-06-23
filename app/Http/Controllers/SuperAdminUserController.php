<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use App\Support\SuperAdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminUserController extends Controller
{
    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function moduleOptions(): array
    {
        return [
            User::SUPER_ADMIN_MODULE_DASHBOARD => [
                'label' => __('Platform dashboard'),
                'description' => __('Access platform-level KPIs and analytics.'),
            ],
            User::SUPER_ADMIN_MODULE_VIBE_PROGRAMME => [
                'label' => __('VIBE Programme'),
                'description' => __('Access business performance, trends, and CSV exports.'),
            ],
            User::SUPER_ADMIN_MODULE_RICA => [
                'label' => __('RICA oversight'),
                'description' => __('Regulatory oversight of slaughterhouses, inspections, and compliance reports.'),
            ],
            User::SUPER_ADMIN_MODULE_CONFIGURATION => [
                'label' => __('Global configuration'),
                'description' => __('Manage species, units, and platform-wide reference data.'),
            ],
            User::SUPER_ADMIN_MODULE_SYSTEM_SETTINGS => [
                'label' => __('System settings'),
                'description' => __('Access global application settings.'),
            ],
            User::SUPER_ADMIN_MODULE_USER_MANAGEMENT => [
                'label' => __('Admin users'),
                'description' => __('Create and manage super admin accounts and module access.'),
            ],
            User::SUPER_ADMIN_MODULE_USERS => [
                'label' => __('Users'),
                'description' => __('View tenants, registered businesses, and workspace user accounts.'),
            ],
        ];
    }

    public function index(): View
    {
        $users = User::query()
            ->where('is_super_admin', true)
            ->orderByDesc('id')
            ->get();

        return view('super-admin.users.index', [
            'users' => $users,
            'moduleOptions' => self::moduleOptions(),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.users.create', [
            'moduleOptions' => self::moduleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $moduleKeys = array_keys(self::moduleOptions());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'module_permissions' => ['required', 'array', 'min:1'],
            'module_permissions.*' => ['required', 'string', Rule::in($moduleKeys)],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_super_admin' => true,
            'super_admin_permissions' => collect($validated['module_permissions'])
                ->map(fn ($permission) => (string) $permission)
                ->unique()
                ->values()
                ->all(),
        ]);

        return redirect()
            ->route('super-admin.users.index')
            ->with('status', __('Super admin user created successfully.'));
    }

    public function edit(User $user): View
    {
        abort_unless($user->isSuperAdmin(), 404);

        return view('super-admin.users.edit', [
            'targetUser' => $user,
            'moduleOptions' => self::moduleOptions(),
            'selectedPermissions' => $user->normalizedSuperAdminPermissions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isSuperAdmin(), 404);

        if ((int) $user->id === (int) $request->user()?->id) {
            return redirect()
                ->route('super-admin.users.index')
                ->with('error', __('You cannot modify your own super admin access here. Use profile settings for your account details.'));
        }

        $moduleKeys = array_keys(self::moduleOptions());
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'module_permissions' => ['required', 'array', 'min:1'],
            'module_permissions.*' => ['required', 'string', Rule::in($moduleKeys)],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->super_admin_permissions = collect($validated['module_permissions'])
            ->map(fn ($permission) => (string) $permission)
            ->unique()
            ->values()
            ->all();

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('super-admin.users.index')
            ->with('status', __('Super admin user updated successfully.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isSuperAdmin(), 404);

        if ((int) $user->id === (int) $request->user()?->id) {
            return redirect()
                ->route('super-admin.users.index')
                ->with('error', __('You cannot remove your own account.'));
        }

        $superAdminCount = User::query()->where('is_super_admin', true)->count();
        if ($superAdminCount <= 1) {
            return redirect()
                ->route('super-admin.users.index')
                ->with('error', __('Cannot remove the last super admin account.'));
        }

        $user->delete();

        return redirect()
            ->route('super-admin.users.index')
            ->with('status', __('Super admin user removed.'));
    }

    public function updateTenantEnvironment(Request $request, User $tenant): RedirectResponse
    {
        if ($tenant->isSuperAdmin()) {
            return redirect()
                ->route('super-admin.tenants.index')
                ->with('error', __('Super admin accounts cannot be marked as test tenants.'));
        }

        $validated = $request->validate([
            'tenant_environment' => ['required', 'string', Rule::in(User::tenantEnvironmentOptions())],
        ]);

        $tenant->tenant_environment = $validated['tenant_environment'];
        $tenant->save();

        $label = $tenant->isTestTenant() ? __('test') : __('live');

        return redirect()
            ->back()
            ->with('status', __('Tenant :name marked as :environment.', [
                'name' => $tenant->name,
                'environment' => $label,
            ]));
    }

    public function destroyTenant(Request $request, User $tenant): RedirectResponse
    {
        if ($tenant->isSuperAdmin()) {
            return redirect()
                ->route('super-admin.tenants.index')
                ->with('error', __('Super admin accounts cannot be deleted as tenants.'));
        }

        if ((int) $tenant->id === (int) $request->user()?->id) {
            return redirect()
                ->route('super-admin.tenants.index')
                ->with('error', __('You cannot delete your own account.'));
        }

        $result = DB::transaction(fn () => $this->deleteTenantCascade($tenant, $request->user()));

        return redirect()
            ->route('super-admin.tenants.index')
            ->with('status', __('Tenant :name deleted. :businesses businesses, :staff staff accounts, and all associated data were permanently removed.', [
                'name' => $result['tenant_name'],
                'businesses' => $result['business_count'],
                'staff' => $result['staff_count'],
            ]));
    }

    public function destroyTenantsBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_ids' => ['required', 'array', 'min:1'],
            'tenant_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);

        $selectedIds = collect($validated['tenant_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $currentUserId = (int) ($request->user()?->id ?? 0);
        if ($selectedIds->contains($currentUserId)) {
            return redirect()
                ->route('super-admin.tenants.index')
                ->with('error', __('You cannot delete your own account.'));
        }

        $tenants = User::query()
            ->whereIn('id', $selectedIds)
            ->where('is_super_admin', false)
            ->get();

        if ($tenants->isEmpty()) {
            return redirect()
                ->route('super-admin.tenants.index')
                ->with('error', __('No valid tenant accounts were selected.'));
        }

        DB::transaction(function () use ($tenants, $request): void {
            foreach ($tenants as $tenant) {
                $this->deleteTenantCascade($tenant, $request->user());
            }
        });

        return redirect()
            ->route('super-admin.tenants.index')
            ->with('status', __(':count selected tenant(s) deleted. Associated businesses, staff accounts, and all related data were permanently removed.', [
                'count' => $tenants->count(),
            ]));
    }

    /**
     * @return array{
     *   tenant_name: string,
     *   business_ids: list<int>,
     *   staff_user_ids: list<int>,
     *   business_count: int,
     *   staff_count: int
     * }
     */
    private function deleteTenantCascade(User $tenant, ?User $actor = null): array
    {
        $ownedBusinessIds = Business::query()
            ->where('user_id', $tenant->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $deletedStaffIds = [];

        $associatedUserIds = DB::table('business_user')
            ->whereIn('business_id', $ownedBusinessIds)
            ->pluck('user_id')
            ->unique()
            ->reject(fn ($userId) => (int) $userId === (int) $tenant->id)
            ->values();

        foreach ($associatedUserIds as $userId) {
            $associatedUser = User::query()->find((int) $userId);
            if ($associatedUser === null || $associatedUser->isSuperAdmin()) {
                continue;
            }

            $hasOwnedBusinessesOutsideTenant = Business::query()
                ->where('user_id', $associatedUser->id)
                ->whereNotIn('id', $ownedBusinessIds)
                ->exists();

            $hasMembershipOutsideTenant = DB::table('business_user')
                ->where('user_id', $associatedUser->id)
                ->whereNotIn('business_id', $ownedBusinessIds)
                ->exists();

            if (! $hasOwnedBusinessesOutsideTenant && ! $hasMembershipOutsideTenant) {
                $deletedStaffIds[] = (int) $associatedUser->id;
                $associatedUser->delete();
            }
        }

        $tenantName = (string) $tenant->name;
        $tenantId = (int) $tenant->id;
        $tenant->delete();

        if ($actor !== null) {
            SuperAdminActivityLogger::log($actor, 'tenant.deleted', [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenantName,
                'business_ids' => $ownedBusinessIds,
                'staff_user_ids' => $deletedStaffIds,
                'business_count' => count($ownedBusinessIds),
                'staff_count' => count($deletedStaffIds),
            ]);
        }

        return [
            'tenant_name' => $tenantName,
            'business_ids' => $ownedBusinessIds,
            'staff_user_ids' => $deletedStaffIds,
            'business_count' => count($ownedBusinessIds),
            'staff_count' => count($deletedStaffIds),
        ];
    }
}
