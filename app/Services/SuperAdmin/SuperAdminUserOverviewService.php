<?php

namespace App\Services\SuperAdmin;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SuperAdminUserOverviewService
{
    public const WORKSPACE_QUERY = 'workspace';

    /** @var list<string> */
    public const WORKSPACE_TYPES = [
        Business::TYPE_PROCESSOR,
        Business::TYPE_BUTCHER,
        Business::TYPE_LOGISTICS,
    ];

    public static function resolveWorkspaceType(?string $value): ?string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($value, self::WORKSPACE_TYPES, true) ? $value : null;
    }

    /**
     * @return array{
     *     kpis: array<string, array{value: int|string, hint: string|null}>,
     *     insights: list<array{label: string, value: string, description: string}>,
     *     chartSpecs: list<array<string, mixed>>,
     *     roleRows: list<array{role: string, users: int, share: int}>,
     *     topTenants: list<array{name: string, email: string, users: int, businesses: int, environment: string}>,
     *     recentUsers: list<array{name: string, email: string, joined: string, kind: string}>,
     *     quietUsers: list<array{name: string, email: string, last_seen: string, kind: string}>
     * }
     */
    public function build(?string $workspaceType = null): array
    {
        $workspaceType = self::resolveWorkspaceType($workspaceType);

        $ownerIds = TenantEnvironmentScope::applyToTenantOwners(
            User::query()
                ->where('is_super_admin', false)
                ->whereHas('businesses', function ($query) use ($workspaceType): void {
                    TenantEnvironmentScope::applyToBusinesses($query);
                    if ($workspaceType !== null) {
                        $query->where('type', $workspaceType);
                    }
                })
        )->pluck('id');

        $businessQuery = Business::query();
        if ($workspaceType !== null) {
            $businessQuery->where('type', $workspaceType);
        }
        $businessIds = TenantEnvironmentScope::applyToBusinesses($businessQuery)->pluck('id');

        $memberships = $businessIds->isEmpty()
            ? collect()
            : BusinessUser::query()
                ->whereIn('business_id', $businessIds)
                ->get(['user_id', 'business_id', 'role']);

        $memberIds = $memberships->pluck('user_id')->unique()->values();
        $workspaceIds = $ownerIds->merge($memberIds)->unique()->values();

        $users = $workspaceIds->isEmpty()
            ? collect()
            : User::query()
                ->whereIn('id', $workspaceIds)
                ->get(['id', 'name', 'email', 'email_verified_at', 'created_at', 'is_super_admin', 'tenant_environment']);

        $owners = $users->whereIn('id', $ownerIds->all());
        $staff = $users->whereIn('id', $memberIds->all());
        $ownerIdSet = $ownerIds->flip();
        $memberIdSet = $memberIds->flip();

        $superAdminCount = User::query()->where('is_super_admin', true)->count();
        $verified = $users->filter(fn (User $user) => $user->email_verified_at !== null)->count();
        $unverified = $users->count() - $verified;

        $thisMonth = $users->filter(fn (User $user) => $user->created_at?->isCurrentMonth())->count();
        $lastMonth = $users->filter(fn (User $user) => $user->created_at?->isLastMonth())->count();

        $activity = $this->activityBuckets($workspaceIds);
        $total = $users->count();
        $active30 = $activity['active_30d'];
        $engagementRate = $total > 0 ? (int) round($active30 / $total * 100) : 0;

        $singleUserTenants = $this->tenantUserCounts($ownerIds)->filter(fn (int $count) => $count <= 1)->count();
        $multiBusinessUsers = $memberships
            ->groupBy('user_id')
            ->filter(fn (Collection $rows) => $rows->pluck('business_id')->unique()->count() > 1)
            ->count();

        $avgUsersPerTenant = $ownerIds->count() > 0
            ? round($total / $ownerIds->count(), 1)
            : 0;

        $roleRows = $this->roleDistribution($memberships, $total);
        $chartSpecs = $this->chartSpecs($users, $roleRows);

        return [
            'kpis' => [
                'total_users' => [
                    'value' => $total,
                    'hint' => __('Workspace accounts in this data scope'),
                ],
                'tenant_owners' => [
                    'value' => $owners->count(),
                    'hint' => __('Users who own at least one business'),
                ],
                'staff_accounts' => [
                    'value' => $staff->count(),
                    'hint' => __('Assigned through a business role'),
                ],
                'super_admins' => [
                    'value' => $superAdminCount,
                    'hint' => __('Platform operators (not scoped)'),
                ],
                'verified' => [
                    'value' => $verified,
                    'hint' => $total > 0
                        ? __(':percent% of workspace users', ['percent' => (int) round($verified / $total * 100)])
                        : __('No workspace users yet'),
                ],
                'unverified' => [
                    'value' => $unverified,
                    'hint' => __('Pending email confirmation'),
                ],
                'new_this_month' => [
                    'value' => $thisMonth,
                    'hint' => $this->monthDeltaHint($thisMonth, $lastMonth),
                ],
                'online_now' => [
                    'value' => $activity['online'],
                    'hint' => __('Signed in within the last 15 minutes'),
                ],
                'active_7d' => [
                    'value' => $activity['active_7d'],
                    'hint' => __('Distinct users with a session this week'),
                ],
                'active_30d' => [
                    'value' => $active30,
                    'hint' => $total > 0
                        ? __(':percent% engagement rate', ['percent' => $engagementRate])
                        : __('No workspace users yet'),
                ],
            ],
            'insights' => [
                [
                    'label' => __('Engagement rate'),
                    'value' => $engagementRate.'%',
                    'description' => __('Share of workspace users active in the last 30 days.'),
                ],
                [
                    'label' => __('Avg users / tenant'),
                    'value' => (string) $avgUsersPerTenant,
                    'description' => __('Workspace users divided by tenant owners in scope.'),
                ],
                [
                    'label' => __('Single-user tenants'),
                    'value' => (string) $singleUserTenants,
                    'description' => __('Tenants whose only account is the owner.'),
                ],
                [
                    'label' => __('Multi-business users'),
                    'value' => (string) $multiBusinessUsers,
                    'description' => __('People assigned to more than one business.'),
                ],
                [
                    'label' => __('Never signed in'),
                    'value' => (string) $activity['never'],
                    'description' => __('Workspace users with no recorded session.'),
                ],
                [
                    'label' => __('Owner + staff overlap'),
                    'value' => (string) $users->filter(
                        fn (User $user) => $ownerIdSet->has($user->id) && $memberIdSet->has($user->id)
                    )->count(),
                    'description' => __('Owners who also hold a business_user role.'),
                ],
            ],
            'chartSpecs' => $chartSpecs,
            'roleRows' => $roleRows,
            'topTenants' => $this->topTenants($ownerIds),
            'recentUsers' => $this->recentUsers($users, $ownerIdSet, $memberIdSet),
            'quietUsers' => $this->quietUsers($users, $activity['last_seen'], $ownerIdSet, $memberIdSet),
        ];
    }

    /**
     * @param  Collection<int, int>  $workspaceIds
     * @return array{online: int, active_7d: int, active_30d: int, dormant: int, never: int, last_seen: array<int, int>}
     */
    private function activityBuckets(Collection $workspaceIds): array
    {
        $empty = [
            'online' => 0,
            'active_7d' => 0,
            'active_30d' => 0,
            'dormant' => 0,
            'never' => 0,
            'last_seen' => [],
        ];

        if ($workspaceIds->isEmpty() || ! Schema::hasTable('sessions')) {
            $empty['never'] = $workspaceIds->count();

            return $empty;
        }

        $now = now()->timestamp;
        $rows = DB::table('sessions')
            ->whereIn('user_id', $workspaceIds->all())
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->groupBy('user_id')
            ->get();

        $lastSeen = [];
        foreach ($rows as $row) {
            $lastSeen[(int) $row->user_id] = (int) $row->last_activity;
        }

        $onlineCutoff = $now - 15 * 60;
        $weekCutoff = $now - 7 * 24 * 60 * 60;
        $monthCutoff = $now - 30 * 24 * 60 * 60;

        $online = 0;
        $week = 0;
        $month = 0;
        $dormant = 0;

        foreach ($lastSeen as $timestamp) {
            if ($timestamp >= $onlineCutoff) {
                $online++;
            }
            if ($timestamp >= $weekCutoff) {
                $week++;
            }
            if ($timestamp >= $monthCutoff) {
                $month++;
            } else {
                $dormant++;
            }
        }

        return [
            'online' => $online,
            'active_7d' => $week,
            'active_30d' => $month,
            'dormant' => $dormant,
            'never' => max(0, $workspaceIds->count() - count($lastSeen)),
            'last_seen' => $lastSeen,
        ];
    }

    /**
     * @param  Collection<int, BusinessUser>  $memberships
     * @return list<array{role: string, users: int, share: int}>
     */
    private function roleDistribution(Collection $memberships, int $totalUsers): array
    {
        $uniqueByRole = $memberships
            ->groupBy('role')
            ->map(fn (Collection $rows) => $rows->pluck('user_id')->unique()->count())
            ->sortDesc();

        $colors = config('bucha.chart.series', []);
        $rows = [];
        $index = 0;

        foreach ($uniqueByRole as $role => $count) {
            $rows[] = [
                'role' => $this->roleLabel((string) $role),
                'role_key' => (string) $role,
                'users' => (int) $count,
                'share' => $totalUsers > 0 ? (int) round($count / $totalUsers * 100) : 0,
                'color' => $colors[$index % max(1, count($colors))] ?? '#718096',
            ];
            $index++;
        }

        return $rows;
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  list<array{role: string, users: int, share: int, color?: string}>  $roleRows
     * @return list<array<string, mixed>>
     */
    private function chartSpecs(Collection $users, array $roleRows): array
    {
        $series = config('bucha.chart.series', ['#A11D1E', '#7A1516', '#3C3C3B', '#718096', '#D69E2E', '#38A169']);

        $months = collect(range(11, 0))->map(fn (int $ago) => now()->startOfMonth()->subMonths($ago));
        $growthCounts = $months->map(function (Carbon $month) use ($users) {
            return $users->filter(
                fn (User $user) => $user->created_at !== null && $user->created_at->between($month, $month->copy()->endOfMonth())
            )->count();
        })->all();

        $roleLabels = array_column($roleRows, 'role');
        $roleData = array_column($roleRows, 'users');
        $roleColors = array_column($roleRows, 'color');

        return [
            [
                'id' => 'chart-user-growth',
                'title' => __('User growth'),
                'subtitle' => __('New workspace accounts by month'),
                'height' => 220,
                'ariaLabel' => __('User growth'),
                'type' => 'bar',
                'labels' => $months->map(fn (Carbon $month) => $month->translatedFormat('M Y'))->all(),
                'datasets' => [[
                    'label' => __('New users'),
                    'data' => $growthCounts,
                    'backgroundColor' => $series[0] ?? '#A11D1E',
                ]],
                'legend' => [[
                    'color' => $series[0] ?? '#A11D1E',
                    'label' => __('New users'),
                ]],
                'emptyMessage' => __('No user registrations in the last 12 months.'),
            ],
            [
                'id' => 'chart-user-roles',
                'title' => __('Role distribution'),
                'subtitle' => __('Unique people per business role'),
                'height' => 220,
                'ariaLabel' => __('Role distribution'),
                'type' => 'pie',
                'labels' => $roleLabels,
                'data' => $roleData,
                'colors' => $roleColors,
                'legend' => collect($roleRows)->map(fn (array $row) => [
                    'color' => $row['color'] ?? '#718096',
                    'label' => $row['role'],
                ])->all(),
                'emptyMessage' => __('No business roles assigned yet.'),
            ],
        ];
    }

    /**
     * @param  Collection<int, int>  $ownerIds
     * @return list<array{name: string, email: string, users: int, businesses: int, environment: string}>
     */
    private function topTenants(Collection $ownerIds): array
    {
        if ($ownerIds->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ownerIds->all())
            ->withCount('businesses')
            ->with(['businesses.memberUsers:id'])
            ->orderBy('name')
            ->get()
            ->map(function (User $tenant) {
                $memberIds = $tenant->businesses
                    ->flatMap(fn (Business $business) => $business->memberUsers->pluck('id'));
                $userCount = $memberIds->push($tenant->id)->unique()->count();

                return [
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'users' => $userCount,
                    'businesses' => (int) $tenant->businesses_count,
                    'environment' => $tenant->isTestTenant() ? __('Test') : __('Live'),
                ];
            })
            ->sortByDesc('users')
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, int>  $ownerIdSet
     * @param  Collection<int, int>  $memberIdSet
     * @return list<array{name: string, email: string, joined: string, kind: string}>
     */
    private function recentUsers(Collection $users, Collection $ownerIdSet, Collection $memberIdSet): array
    {
        return $users
            ->sortByDesc(fn (User $user) => $user->created_at?->timestamp ?? 0)
            ->take(8)
            ->map(fn (User $user) => [
                'name' => $user->name,
                'email' => $user->email,
                'joined' => $user->created_at?->timezone(config('app.display_timezone', config('app.timezone')))->format('d M Y') ?? '—',
                'kind' => $this->accountKind($user->id, $ownerIdSet, $memberIdSet),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  array<int, int>  $lastSeen
     * @param  Collection<int, int>  $ownerIdSet
     * @param  Collection<int, int>  $memberIdSet
     * @return list<array{name: string, email: string, last_seen: string, kind: string}>
     */
    private function quietUsers(Collection $users, array $lastSeen, Collection $ownerIdSet, Collection $memberIdSet): array
    {
        $monthAgo = now()->subDays(30)->timestamp;

        return $users
            ->filter(function (User $user) use ($lastSeen, $monthAgo) {
                $seen = $lastSeen[$user->id] ?? null;

                return $seen === null || $seen < $monthAgo;
            })
            ->sortBy(fn (User $user) => $lastSeen[$user->id] ?? 0)
            ->take(8)
            ->map(function (User $user) use ($lastSeen, $ownerIdSet, $memberIdSet) {
                $seen = $lastSeen[$user->id] ?? null;

                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'last_seen' => $seen === null
                        ? __('Never')
                        : Carbon::createFromTimestamp($seen)
                            ->timezone(config('app.display_timezone', config('app.timezone')))
                            ->diffForHumans(),
                    'kind' => $this->accountKind($user->id, $ownerIdSet, $memberIdSet),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int>  $ownerIds
     * @return Collection<int, int>
     */
    private function tenantUserCounts(Collection $ownerIds): Collection
    {
        if ($ownerIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ownerIds->all())
            ->with(['businesses.memberUsers:id'])
            ->get()
            ->mapWithKeys(function (User $tenant) {
                $memberIds = $tenant->businesses
                    ->flatMap(fn (Business $business) => $business->memberUsers->pluck('id'));

                return [$tenant->id => $memberIds->push($tenant->id)->unique()->count()];
            });
    }

    /**
     * @param  Collection<int, int>  $ownerIdSet
     * @param  Collection<int, int>  $memberIdSet
     */
    private function accountKind(int $userId, Collection $ownerIdSet, Collection $memberIdSet): string
    {
        $isOwner = $ownerIdSet->has($userId);
        $isStaff = $memberIdSet->has($userId);

        if ($isOwner && $isStaff) {
            return __('Owner & staff');
        }
        if ($isOwner) {
            return __('Tenant owner');
        }
        if ($isStaff) {
            return __('Staff');
        }

        return __('User');
    }

    private function monthDeltaHint(int $thisMonth, int $lastMonth): string
    {
        if ($lastMonth === 0 && $thisMonth === 0) {
            return __('No new accounts this month or last');
        }
        if ($lastMonth === 0) {
            return __('Up from none last month');
        }

        $delta = (int) round(($thisMonth - $lastMonth) / $lastMonth * 100);
        if ($delta === 0) {
            return __('Flat versus last month');
        }
        if ($delta > 0) {
            return __('Up :percent% versus last month', ['percent' => $delta]);
        }

        return __('Down :percent% versus last month', ['percent' => abs($delta)]);
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            BusinessUser::ROLE_ORG_ADMIN => __('Org admin'),
            BusinessUser::ROLE_OPERATIONS_MANAGER => __('Operations manager'),
            BusinessUser::ROLE_COMPLIANCE_OFFICER => __('Compliance officer'),
            BusinessUser::ROLE_INSPECTOR => __('Inspector'),
            BusinessUser::ROLE_TRANSPORT_MANAGER => __('Transport manager'),
            BusinessUser::ROLE_ACCOUNTANT => __('Accountant'),
            BusinessUser::ROLE_SALES_MARKETING_OFFICER => __('Sales & marketing'),
            default => str_replace('_', ' ', ucfirst($role)),
        };
    }
}
