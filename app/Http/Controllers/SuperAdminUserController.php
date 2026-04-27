<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
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

    public function destroyTenant(Request $request, User $tenant): RedirectResponse
    {
        if ($tenant->isSuperAdmin()) {
            return redirect()
                ->route('super-admin.dashboard')
                ->with('error', __('Super admin accounts cannot be deleted as tenants.'));
        }

        if ((int) $tenant->id === (int) $request->user()?->id) {
            return redirect()
                ->route('super-admin.dashboard')
                ->with('error', __('You cannot delete your own account.'));
        }

        $this->deleteTenantCascade($tenant);

        return redirect()
            ->route('super-admin.dashboard')
            ->with('status', __('Tenant deleted. All associated businesses were removed, and tenant-only users were deleted.'));
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
                ->route('super-admin.dashboard')
                ->with('error', __('You cannot delete your own account.'));
        }

        $tenants = User::query()
            ->whereIn('id', $selectedIds)
            ->where('is_super_admin', false)
            ->get();

        if ($tenants->isEmpty()) {
            return redirect()
                ->route('super-admin.dashboard')
                ->with('error', __('No valid tenant accounts were selected.'));
        }

        DB::transaction(function () use ($tenants): void {
            foreach ($tenants as $tenant) {
                $this->deleteTenantCascade($tenant);
            }
        });

        return redirect()
            ->route('super-admin.dashboard')
            ->with('status', __('Selected tenants deleted. Associated businesses were removed, and tenant-only users were deleted.'));
    }

    private function deleteTenantCascade(User $tenant): void
    {
        $ownedBusinessIds = Business::query()
            ->where('user_id', $tenant->id)
            ->pluck('id');

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
                $associatedUser->delete();
            }
        }

        $tenant->delete();
    }
}
