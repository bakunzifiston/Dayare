<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessRolePermissionOverride;
use App\Models\BusinessUser;
use App\Models\User;
use App\Support\ProcessorRolePermissionCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProcessorRolePermissionController extends Controller
{
    public function index(Request $request): View
    {
        $businesses = $this->ownedProcessorBusinesses($request);
        $this->authorizeOwner($request, $businesses);

        $business = $this->selectedBusiness($request, $businesses);
        $role = $this->selectedRole($request);
        $roleGuidance = TenantUserController::roleGuidance((int) $business->id);
        $effectivePermissions = BusinessUser::permissionsForRole($role, (int) $business->id);
        $defaultPermissions = BusinessUser::defaultPermissionsForRole($role);
        $overrides = BusinessRolePermissionOverride::query()
            ->where('business_id', $business->id)
            ->where('role', $role)
            ->pluck('is_allowed', 'permission')
            ->all();

        return view('tenant-users.role-permissions', [
            'businesses' => $businesses,
            'selectedBusiness' => $business,
            'selectedRole' => $role,
            'roleOptions' => TenantUserController::roleOptions(),
            'roleDescription' => $roleGuidance[$role]['description'] ?? '',
            'roleMemberCount' => BusinessUser::query()
                ->where('business_id', $business->id)
                ->where('role', $role)
                ->count(),
            'permissionGroups' => ProcessorRolePermissionCatalog::groups(),
            'effectivePermissions' => $effectivePermissions,
            'defaultPermissions' => $defaultPermissions,
            'overrides' => $overrides,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $businesses = $this->ownedProcessorBusinesses($request);
        $this->authorizeOwner($request, $businesses);
        $businessIds = $businesses->modelKeys();

        $validated = $request->validate([
            'business_id' => ['required', 'integer', Rule::in($businessIds)],
            'role' => ['required', 'string', Rule::in(BusinessUser::ROLES)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(BusinessUser::ACTION_PERMISSIONS)],
        ]);

        $businessId = (int) $validated['business_id'];
        $role = $validated['role'];
        $selectedPermissions = collect($validated['permissions'] ?? [])
            ->unique()
            ->values()
            ->all();
        $defaultPermissions = BusinessUser::defaultPermissionsForRole($role);

        DB::transaction(function () use (
            $businessId,
            $role,
            $selectedPermissions,
            $defaultPermissions
        ): void {
            BusinessRolePermissionOverride::query()
                ->where('business_id', $businessId)
                ->where('role', $role)
                ->delete();

            foreach (BusinessUser::ACTION_PERMISSIONS as $permission) {
                $isAllowed = in_array($permission, $selectedPermissions, true);
                $isAllowedByDefault = in_array($permission, $defaultPermissions, true);

                if ($isAllowed === $isAllowedByDefault) {
                    continue;
                }

                BusinessRolePermissionOverride::query()->create([
                    'business_id' => $businessId,
                    'role' => $role,
                    'permission' => $permission,
                    'is_allowed' => $isAllowed,
                ]);
            }
        });
        BusinessUser::forgetResolvedPermissions($businessId, $role);
        User::forgetResolvedProcessorPermissions(businessId: $businessId);

        return redirect()
            ->route('tenant-users.role-permissions.index', [
                'business_id' => $businessId,
                'role' => $role,
            ])
            ->with('status', __('Role access updated. Changes apply immediately.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $businesses = $this->ownedProcessorBusinesses($request);
        $this->authorizeOwner($request, $businesses);
        $businessIds = $businesses->modelKeys();

        $validated = $request->validate([
            'business_id' => ['required', 'integer', Rule::in($businessIds)],
            'role' => ['required', 'string', Rule::in(BusinessUser::ROLES)],
        ]);

        BusinessRolePermissionOverride::query()
            ->where('business_id', (int) $validated['business_id'])
            ->where('role', $validated['role'])
            ->delete();
        BusinessUser::forgetResolvedPermissions(
            (int) $validated['business_id'],
            $validated['role']
        );
        User::forgetResolvedProcessorPermissions(
            businessId: (int) $validated['business_id']
        );

        return redirect()
            ->route('tenant-users.role-permissions.index', $validated)
            ->with('status', __('The role has been restored to its system defaults.'));
    }

    private function ownedProcessorBusinesses(Request $request): Collection
    {
        if ($request->user()->isSuperAdmin()) {
            return Business::query()
                ->where('type', Business::TYPE_PROCESSOR)
                ->orderBy('business_name')
                ->get(['id', 'business_name']);
        }

        return $request->user()
            ->businesses()
            ->where('type', Business::TYPE_PROCESSOR)
            ->orderBy('business_name')
            ->get(['id', 'business_name']);
    }

    private function authorizeOwner(Request $request, Collection $businesses): void
    {
        if ($request->user()->isSuperAdmin()) {
            return;
        }

        abort_if($businesses->isEmpty(), 403, __('Only business owners can customize role access.'));
    }

    private function selectedBusiness(Request $request, Collection $businesses): Business
    {
        $selectedId = $request->integer('business_id');
        $business = $selectedId > 0
            ? $businesses->firstWhere('id', $selectedId)
            : $businesses->first();

        abort_if($business === null, 403, __('You cannot customize roles for this business.'));

        return $business;
    }

    private function selectedRole(Request $request): string
    {
        $role = $request->string('role', BusinessUser::ROLE_OPERATIONS_MANAGER)->toString();

        return in_array($role, BusinessUser::ROLES, true)
            ? $role
            : BusinessUser::ROLE_OPERATIONS_MANAGER;
    }
}
