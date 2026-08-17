<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\BusinessUserPermissionOverride;
use App\Models\User;
use App\Support\ProcessorRolePermissionCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProcessorUserPermissionController extends Controller
{
    public function index(Request $request, User $user): View
    {
        $businesses = $this->manageableMembershipBusinesses($request, $user);
        $this->authorizeManager($request, $user, $businesses);
        $business = $this->selectedBusiness($request, $businesses);
        $role = $this->membershipRole($user, $business);
        $rolePermissions = BusinessUser::permissionsForRole($role, (int) $business->id);
        $effectivePermissions = $user->processorPermissionsForBusiness((int) $business->id);
        $overrides = BusinessUserPermissionOverride::query()
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->pluck('is_allowed', 'permission')
            ->all();

        return view('tenant-users.user-permissions', [
            'member' => $user,
            'businesses' => $businesses,
            'selectedBusiness' => $business,
            'role' => $role,
            'roleLabel' => TenantUserController::roleOptions()[$role] ?? $role,
            'permissionGroups' => ProcessorRolePermissionCatalog::groups(),
            'effectivePermissions' => $effectivePermissions,
            'rolePermissions' => $rolePermissions,
            'overrides' => $overrides,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $businesses = $this->manageableMembershipBusinesses($request, $user);
        $this->authorizeManager($request, $user, $businesses);
        $businessIds = $businesses->modelKeys();

        $validated = $request->validate([
            'business_id' => ['required', 'integer', Rule::in($businessIds)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(BusinessUser::ACTION_PERMISSIONS)],
        ]);

        $businessId = (int) $validated['business_id'];
        $business = $businesses->firstWhere('id', $businessId);
        abort_if($business === null, 403);

        $role = $this->membershipRole($user, $business);
        $rolePermissions = BusinessUser::permissionsForRole($role, $businessId);
        $selectedPermissions = collect($validated['permissions'] ?? [])
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use (
            $businessId,
            $user,
            $selectedPermissions,
            $rolePermissions
        ): void {
            BusinessUserPermissionOverride::query()
                ->where('business_id', $businessId)
                ->where('user_id', $user->id)
                ->delete();

            foreach (BusinessUser::ACTION_PERMISSIONS as $permission) {
                $isAllowed = in_array($permission, $selectedPermissions, true);
                $isAllowedByRole = in_array($permission, $rolePermissions, true);

                if ($isAllowed === $isAllowedByRole) {
                    continue;
                }

                BusinessUserPermissionOverride::query()->create([
                    'business_id' => $businessId,
                    'user_id' => $user->id,
                    'permission' => $permission,
                    'is_allowed' => $isAllowed,
                ]);
            }
        });
        User::forgetResolvedProcessorPermissions((int) $user->id, $businessId);

        return redirect()
            ->route('tenant-users.user-permissions.index', [
                'user' => $user,
                'business_id' => $businessId,
            ])
            ->with('status', __('Individual access updated. Changes apply immediately.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $businesses = $this->manageableMembershipBusinesses($request, $user);
        $this->authorizeManager($request, $user, $businesses);
        $businessIds = $businesses->modelKeys();

        $validated = $request->validate([
            'business_id' => ['required', 'integer', Rule::in($businessIds)],
        ]);
        $businessId = (int) $validated['business_id'];

        BusinessUserPermissionOverride::query()
            ->where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->delete();
        User::forgetResolvedProcessorPermissions((int) $user->id, $businessId);

        return redirect()
            ->route('tenant-users.user-permissions.index', [
                'user' => $user,
                'business_id' => $businessId,
            ])
            ->with('status', __('The user has been restored to their role access.'));
    }

    private function manageableMembershipBusinesses(Request $request, User $user): Collection
    {
        $query = Business::query()
            ->where('type', Business::TYPE_PROCESSOR)
            ->where('user_id', '!=', $user->id)
            ->whereIn(
                'id',
                BusinessUser::query()
                    ->where('user_id', $user->id)
                    ->pluck('business_id')
            );

        if (! $request->user()->isSuperAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        return $query->orderBy('business_name')->get(['id', 'business_name']);
    }

    private function authorizeManager(Request $request, User $user, Collection $businesses): void
    {
        abort_if($request->user()->is($user), 403, __('You cannot customize your own access.'));
        abort_if(
            ! $request->user()->isSuperAdmin() && $businesses->isEmpty(),
            403,
            __('Only business owners can customize individual access.')
        );
        abort_if($businesses->isEmpty(), 403, __('This user has no customizable processor membership.'));
        abort_if(
            $businesses->contains(fn (Business $business): bool => $user->ownsBusiness((int) $business->id)),
            403,
            __('Business owners keep full access and cannot be customized.')
        );
    }

    private function selectedBusiness(Request $request, Collection $businesses): Business
    {
        $selectedId = $request->integer('business_id');
        $business = $selectedId > 0
            ? $businesses->firstWhere('id', $selectedId)
            : $businesses->first();

        abort_if($business === null, 403, __('You cannot customize this user for the selected business.'));

        return $business;
    }

    private function membershipRole(User $user, Business $business): string
    {
        $role = BusinessUser::query()
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->value('role');

        abort_unless(in_array($role, BusinessUser::ROLES, true), 422, __('Assign a valid role before customizing this user.'));

        return $role;
    }
}
