<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class TenantUserController extends Controller
{
    /** Permission names grouped by module for the "select modules" UI. */
    public static function permissionGroups(): array
    {
        $all = Permission::where('guard_name', 'web')->orderBy('name')->pluck('name');
        $groups = [
            __('Operations') => [],
            __('CRM & HR') => [],
            __('Settings') => [],
        ];
        $ops = ['manage businesses', 'manage facilities', 'manage inspectors', 'manage animal intakes', 'manage slaughter plans', 'manage slaughter executions', 'manage batches', 'manage ante-mortem', 'manage post-mortem', 'manage certificates', 'manage warehouse', 'manage transport', 'manage delivery confirmations', 'view compliance', 'view divisions'];
        $crm = ['manage employees', 'manage suppliers', 'manage contracts', 'view crm', 'manage clients', 'manage demands', 'view recipients'];
        $settings = ['manage settings', 'manage species', 'manage units'];
        foreach ($all as $name) {
            if (in_array($name, $ops, true)) {
                $groups[__('Operations')][$name] = $name;
            } elseif (in_array($name, $crm, true)) {
                $groups[__('CRM & HR')][$name] = $name;
            } elseif (in_array($name, $settings, true)) {
                $groups[__('Settings')][$name] = $name;
            }
        }
        return $groups;
    }

    /** @return array<int> */
    private function myBusinessIds(Request $request): array
    {
        return $request->user()->businesses()->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /** @return array<int> User IDs that are members of the current user's businesses (excluding owner). */
    private function memberUserIds(Request $request): array
    {
        $ids = $this->myBusinessIds($request);
        if ($ids === []) {
            return [];
        }
        $raw = DB::table('business_user')->whereIn('business_id', $ids)->pluck('user_id')->unique()->values()->all();
        return array_map('intval', $raw);
    }

    /** Check if user is a member of any of the current user's businesses (direct DB check). */
    private function userIsInMyTeam(Request $request, int $userId): bool
    {
        $ids = $this->myBusinessIds($request);
        if ($ids === []) {
            return false;
        }
        return DB::table('business_user')
            ->where('user_id', $userId)
            ->whereIn('business_id', $ids)
            ->exists();
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->canManageTenantUsers()) {
            abort(403, __('Only business owners can manage users.'));
        }

        $myBusinessIds = $this->myBusinessIds($request);
        $memberUserIds = $this->memberUserIds($request);
        $users = User::where('id', $request->user()->id)
            ->orWhereIn('id', $memberUserIds)
            ->with(['businesses', 'memberBusinesses'])
            ->orderBy('name')
            ->get();

        $userBusinessRoles = [];
        foreach ($users as $u) {
            if ((int) $u->id === (int) $request->user()->id) {
                $userBusinessRoles[$u->id] = [['business_name' => __('All your businesses'), 'role' => __('Owner')]];
            } else {
                $pivots = BusinessUser::where('user_id', (int) $u->id)->whereIn('business_id', $myBusinessIds)->with('business:id,business_name')->get();
                $userBusinessRoles[$u->id] = $pivots->map(fn ($p) => ['business_name' => $p->business?->business_name ?? '—', 'role' => $p->role])->all();
            }
        }

        return view('tenant-users.index', compact('users', 'userBusinessRoles'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->canManageTenantUsers()) {
            abort(403, __('Only business owners can manage users.'));
        }

        $businesses = $request->user()->businesses()->orderBy('business_name')->get();
        $permissionGroups = self::permissionGroups();

        return view('tenant-users.create', compact('businesses', 'permissionGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->canManageTenantUsers()) {
            abort(403, __('Only business owners can manage users.'));
        }

        $myBusinessIds = $this->myBusinessIds($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(BusinessUser::ROLES)],
            'business_ids' => ['required', 'array', 'min:1'],
            'business_ids.*' => ['integer', Rule::in($myBusinessIds)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Permission::where('guard_name', 'web')->pluck('name')->all())],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        foreach ($validated['business_ids'] as $businessId) {
            BusinessUser::create([
                'business_id' => $businessId,
                'user_id' => $user->id,
                'role' => $validated['role'],
            ]);
        }

        $permissions = $validated['permissions'] ?? [];
        if (! empty($permissions)) {
            $user->syncPermissions($permissions);
        } else {
            $user->assignRole($validated['role']);
        }

        return redirect()->route('tenant-users.index')->with('status', __('User created successfully.'));
    }

    public function edit(Request $request, User $tenant_user): View|RedirectResponse
    {
        if (! $request->user()->canManageTenantUsers()) {
            abort(403, __('Only business owners can manage users.'));
        }

        $me = $request->user();
        $user = $tenant_user;
        $userId = (int) $user->id;
        if ($userId === (int) $me->id) {
            return redirect()->route('tenant-users.index')->with('info', __('You can edit your own profile from Profile.'));
        }

        if (! $this->userIsInMyTeam($request, $userId)) {
            abort(404, __('User not found in your team.'));
        }

        $myBusinessIds = $this->myBusinessIds($request);
        $businesses = $me->businesses()->orderBy('business_name')->get();
        $userBusinessIds = DB::table('business_user')
            ->where('user_id', $userId)
            ->whereIn('business_id', $myBusinessIds)
            ->pluck('business_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $currentRole = DB::table('business_user')
            ->where('user_id', $userId)
            ->whereIn('business_id', $myBusinessIds)
            ->value('role') ?? BusinessUser::ROLE_STAFF;
        $permissionGroups = self::permissionGroups();
        $userPermissionNames = $user->getPermissionNames()->all();

        return view('tenant-users.edit', compact('user', 'businesses', 'permissionGroups', 'userBusinessIds', 'currentRole', 'userPermissionNames'));
    }

    public function update(Request $request, User $tenant_user): RedirectResponse
    {
        if (! $request->user()->canManageTenantUsers()) {
            abort(403, __('Only business owners can manage users.'));
        }

        $user = $tenant_user;
        $me = $request->user();
        $userId = (int) $user->id;
        if ($userId === (int) $me->id) {
            return redirect()->route('tenant-users.index');
        }

        if (! $this->userIsInMyTeam($request, $userId)) {
            abort(404, __('User not found in your team.'));
        }

        $myBusinessIds = $this->myBusinessIds($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(BusinessUser::ROLES)],
            'business_ids' => ['required', 'array', 'min:1'],
            'business_ids.*' => ['integer', Rule::in($myBusinessIds)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Permission::where('guard_name', 'web')->pluck('name')->all())],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);
        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        BusinessUser::where('user_id', $user->id)->whereIn('business_id', $myBusinessIds)->delete();
        foreach ($validated['business_ids'] as $businessId) {
            BusinessUser::create([
                'business_id' => $businessId,
                'user_id' => $user->id,
                'role' => $validated['role'],
            ]);
        }

        $permissions = $validated['permissions'] ?? [];
        $user->syncRoles([]);
        if (! empty($permissions)) {
            $user->syncPermissions($permissions);
        } else {
            $user->assignRole($validated['role']);
        }

        return redirect()->route('tenant-users.index')->with('status', __('User updated successfully.'));
    }

    public function destroy(Request $request, User $tenant_user): RedirectResponse
    {
        if (! $request->user()->canManageTenantUsers()) {
            abort(403, __('Only business owners can manage users.'));
        }

        $user = $tenant_user;
        $me = $request->user();
        $userId = (int) $user->id;
        if ($userId === (int) $me->id) {
            return redirect()->route('tenant-users.index')->with('error', __('You cannot remove yourself.'));
        }
        if (! $this->userIsInMyTeam($request, $userId)) {
            abort(404, __('User not found in your team.'));
        }
        $myBusinessIds = $this->myBusinessIds($request);
        BusinessUser::where('user_id', $userId)->whereIn('business_id', $myBusinessIds)->delete();
        $user->syncPermissions([]);
        $user->syncRoles([]);

        return redirect()->route('tenant-users.index')->with('status', __('User removed from your team.'));
    }
}
