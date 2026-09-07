<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use App\Services\SuperAdmin\SuperAdminUserOverviewService;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SuperAdminTenantController extends Controller
{
    public function __construct(
        private readonly SuperAdminUserOverviewService $userOverview,
    ) {}

    public function overview(Request $request): View
    {
        $tenantEnvironmentFilter = TenantEnvironmentScope::resolveFromRequest($request);
        TenantEnvironmentScope::setFilter($tenantEnvironmentFilter);
        $workspaceType = SuperAdminUserOverviewService::resolveWorkspaceType(
            $request->query(SuperAdminUserOverviewService::WORKSPACE_QUERY)
        );

        return view('super-admin.tenants.overview', array_merge(
            $this->userOverview->build($workspaceType),
            [
                'tenantEnvironmentFilter' => $tenantEnvironmentFilter,
                'workspaceType' => $workspaceType,
            ],
        ));
    }

    public function index(Request $request): View
    {
        $tenantEnvironmentFilter = TenantEnvironmentScope::resolveFromRequest($request);
        TenantEnvironmentScope::setFilter($tenantEnvironmentFilter);
        $workspaceType = SuperAdminUserOverviewService::resolveWorkspaceType(
            $request->query(SuperAdminUserOverviewService::WORKSPACE_QUERY)
        );

        return view('super-admin.tenants.index', [
            'tenantRows' => $this->tenantRows($workspaceType),
            'tenantUserRows' => $this->tenantUserRows($workspaceType),
            'tenantEnvironmentFilter' => $tenantEnvironmentFilter,
            'workspaceType' => $workspaceType,
        ]);
    }

    public function show(User $tenant): View
    {
        abort_if($tenant->isSuperAdmin(), 404);

        $tenant->load([
            'businesses' => function ($query): void {
                $query->withCount(['facilities', 'memberUsers'])
                    ->with([
                        'memberUsers:id,name,email,email_verified_at,created_at',
                        'provinceDivision:id,name',
                        'districtDivision:id,name',
                        'sectorDivision:id,name',
                    ])
                    ->orderBy('business_name');
            },
        ]);

        $staffIds = $tenant->businesses
            ->flatMap(fn (Business $business) => $business->memberUsers->pluck('id'))
            ->unique()
            ->values();
        $userIds = $staffIds->concat([$tenant->id])->unique()->values();
        $lastSignIns = $this->lastSignIns($userIds->all());

        $userRows = collect([
            [
                'name' => $tenant->name,
                'email' => $tenant->email,
                'role' => __('Tenant owner'),
                'business' => $tenant->businesses->pluck('business_name')->filter()->join(', ') ?: '—',
                'verified' => $tenant->email_verified_at !== null,
                'joined' => $this->formatDateTime($tenant->created_at),
                'last_sign_in' => $this->formatLastSignIn($lastSignIns[$tenant->id] ?? null),
            ],
        ]);

        foreach ($tenant->businesses as $business) {
            foreach ($business->memberUsers as $member) {
                $userRows->push([
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => str_replace('_', ' ', ucfirst((string) ($member->pivot?->role ?? __('User')))),
                    'business' => $business->business_name,
                    'verified' => $member->email_verified_at !== null,
                    'joined' => $this->formatDateTime($member->created_at),
                    'last_sign_in' => $this->formatLastSignIn($lastSignIns[$member->id] ?? null),
                ]);
            }
        }

        $businessRows = $tenant->businesses->map(function (Business $business) {
            $location = collect([
                $business->provinceDivision?->name,
                $business->districtDivision?->name,
                $business->sectorDivision?->name,
                $business->city,
            ])->filter()->unique()->implode(', ');

            $ownerName = trim((string) (($business->owner_first_name ?? '').' '.($business->owner_last_name ?? '')));
            if ($ownerName === '') {
                $ownerName = (string) ($business->owner_name ?: '');
            }

            return [
                'name' => $business->business_name ?: '—',
                'type' => ucfirst((string) ($business->type ?: '—')),
                'status' => ucfirst((string) ($business->status ?: '—')),
                'registration_number' => $business->registration_number ?: '—',
                'tax_id' => $business->tax_id ?: '—',
                'email' => $business->email ?: '—',
                'phone' => $business->contact_phone ?: '—',
                'owner' => $ownerName !== '' ? $ownerName : '—',
                'location' => $location !== '' ? $location : '—',
                'vibe_id' => $business->vibe_unique_id ?: '—',
                'pathway' => ucfirst((string) ($business->pathway_status ?: '—')),
                'ownership' => ucfirst(str_replace('_', ' ', (string) ($business->ownership_type ?: '—'))),
                'size' => ucfirst((string) ($business->business_size ?: '—')),
                'facilities' => (int) $business->facilities_count,
                'staff' => (int) $business->member_users_count,
                'commenced' => $business->vibe_commencement_date?->format('d M Y') ?? '—',
            ];
        });

        return view('super-admin.tenants.show', [
            'tenant' => $tenant,
            'isTest' => $tenant->isTestTenant(),
            'lastSignIn' => $this->formatLastSignIn($lastSignIns[$tenant->id] ?? null),
            'joined' => $this->formatDateTime($tenant->created_at),
            'businessRows' => $businessRows,
            'userRows' => $userRows->values(),
            'usersCount' => $userIds->count(),
            'staffCount' => $staffIds->count(),
            'tenantEnvironmentFilter' => $tenant->tenant_environment ?: User::TENANT_ENVIRONMENT_LIVE,
        ]);
    }

    private function tenantRows(?string $workspaceType = null)
    {
        $workspaceType = SuperAdminUserOverviewService::resolveWorkspaceType($workspaceType);

        return TenantEnvironmentScope::applyToTenantOwners(
            User::query()->whereHas('businesses', function ($query) use ($workspaceType): void {
                TenantEnvironmentScope::applyToBusinesses($query);
                if ($workspaceType !== null) {
                    $query->where('type', $workspaceType);
                }
            })
        )
            ->withCount(['businesses' => function ($query) use ($workspaceType): void {
                if ($workspaceType !== null) {
                    $query->where('type', $workspaceType);
                }
            }])
            ->with(['businesses' => function ($query) use ($workspaceType): void {
                $query->with('memberUsers:id');
                if ($workspaceType !== null) {
                    $query->where('type', $workspaceType);
                }
            }])
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

    private function tenantUserRows(?string $workspaceType = null)
    {
        $workspaceType = SuperAdminUserOverviewService::resolveWorkspaceType($workspaceType);
        $businessQuery = Business::query();
        if ($workspaceType !== null) {
            $businessQuery->where('type', $workspaceType);
        }

        $businesses = TenantEnvironmentScope::applyToBusinesses($businessQuery)
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
                    'tenant_id' => (int) $business->user_id,
                ]);
            }

            foreach ($business->memberUsers as $member) {
                $rows->push([
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => (string) ($member->pivot?->role ?? __('User')),
                    'tenant' => $business->business_name,
                    'tenant_id' => (int) $business->user_id,
                ]);
            }
        }

        return $rows
            ->sortBy(['tenant', 'name'])
            ->values();
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, int>
     */
    private function lastSignIns(array $userIds): array
    {
        if ($userIds === [] || ! Schema::hasTable('sessions')) {
            return [];
        }

        return DB::table('sessions')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id')
            ->map(fn ($timestamp) => (int) $timestamp)
            ->all();
    }

    private function formatLastSignIn(?int $timestamp): string
    {
        if ($timestamp === null) {
            return __('Never');
        }

        $seen = Carbon::createFromTimestamp($timestamp)
            ->timezone((string) config('app.display_timezone', config('app.timezone')));

        return $seen->format('d M Y H:i').' ('.$seen->diffForHumans().')';
    }

    private function formatDateTime(mixed $value): string
    {
        if (! $value instanceof \DateTimeInterface) {
            return '—';
        }

        return Carbon::parse($value)
            ->timezone((string) config('app.display_timezone', config('app.timezone')))
            ->format('d M Y H:i');
    }
}
