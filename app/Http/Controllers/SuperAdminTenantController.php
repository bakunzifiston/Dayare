<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use App\Support\TenantEnvironmentScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminTenantController extends Controller
{
    public function index(Request $request): View
    {
        $tenantEnvironmentFilter = TenantEnvironmentScope::resolveFromRequest($request);
        TenantEnvironmentScope::setFilter($tenantEnvironmentFilter);

        return view('super-admin.tenants.index', [
            'tenantRows' => $this->tenantRows(),
            'tenantUserRows' => $this->tenantUserRows(),
            'tenantEnvironmentFilter' => $tenantEnvironmentFilter,
        ]);
    }

    private function tenantRows()
    {
        return TenantEnvironmentScope::applyToTenantOwners(
            User::query()->whereHas('businesses')
        )
            ->withCount('businesses')
            ->with(['businesses.memberUsers:id'])
            ->orderByRaw('CASE WHEN COALESCE(tenant_environment, ?) = ? THEN 1 ELSE 0 END', [
                User::TENANT_ENVIRONMENT_LIVE,
                User::TENANT_ENVIRONMENT_TEST,
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $tenant) {
                $memberIds = $tenant->businesses
                    ->flatMap(fn (Business $business) => $business->memberUsers->pluck('id'));
                $userCount = $memberIds
                    ->push($tenant->id)
                    ->unique()
                    ->count();

                $staffCount = $tenant->businesses
                    ->flatMap(fn (Business $business) => $business->memberUsers->pluck('id'))
                    ->unique()
                    ->count();

                return [
                    'id' => (int) $tenant->id,
                    'tenant_name' => $tenant->name,
                    'tenant_email' => $tenant->email,
                    'tenant_environment' => (string) ($tenant->tenant_environment ?? User::TENANT_ENVIRONMENT_LIVE),
                    'staff_count' => (int) $staffCount,
                    'business_names' => $tenant->businesses
                        ->pluck('business_name')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                    'business_types' => $tenant->businesses
                        ->pluck('type')
                        ->filter()
                        ->map(fn ($type) => ucfirst((string) $type))
                        ->unique()
                        ->values()
                        ->all(),
                    'businesses_count' => (int) $tenant->businesses_count,
                    'users_count' => (int) $userCount,
                ];
            });
    }

    private function tenantUserRows()
    {
        $businesses = TenantEnvironmentScope::applyToBusinesses(Business::query())
            ->with([
                'user:id,name,email,tenant_environment',
                'memberUsers:id,name,email',
            ])
            ->orderBy('business_name')
            ->get(['id', 'business_name', 'user_id']);

        $rows = collect();

        foreach ($businesses as $business) {
            if ($business->user !== null) {
                $rows->push([
                    'name' => $business->user->name,
                    'email' => $business->user->email,
                    'role' => BusinessUser::ROLE_ORG_ADMIN,
                    'tenant' => $business->business_name,
                ]);
            }

            foreach ($business->memberUsers as $member) {
                $rows->push([
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => (string) ($member->pivot?->role ?? __('User')),
                    'tenant' => $business->business_name,
                ]);
            }
        }

        return $rows
            ->sortBy(['tenant', 'name'])
            ->values();
    }
}
