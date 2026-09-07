<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">{{ __('Super Admin') }}</span>
            <h1 class="text-xl font-semibold text-slate-800 tracking-tight">
                {{ __('Users') }}
            </h1>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                @include('super-admin.tenants.partials.users-tabs', [
                    'tenantEnvironmentFilter' => $tenantEnvironmentFilter,
                    'workspaceType' => $workspaceType ?? null,
                ])

                <form method="GET" action="{{ route('super-admin.tenants.overview') }}" class="hub-period-filter__toggles" aria-label="{{ __('Workspace type') }}">
                    @if (filled($tenantEnvironmentFilter))
                        <input type="hidden" name="tenant_environment" value="{{ $tenantEnvironmentFilter }}">
                    @endif
                    @foreach ([
                        '' => __('All'),
                        \App\Models\Business::TYPE_PROCESSOR => __('Processor'),
                        \App\Models\Business::TYPE_BUTCHER => __('Butcher'),
                        \App\Models\Business::TYPE_LOGISTICS => __('Logistics'),
                    ] as $value => $label)
                        <label class="hub-period-filter__toggle">
                            <input type="radio"
                                   name="{{ \App\Services\SuperAdmin\SuperAdminUserOverviewService::WORKSPACE_QUERY }}"
                                   value="{{ $value }}"
                                   @checked(($workspaceType ?? '') === $value)
                                   onchange="this.form.submit()">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </form>
            </div>

            <div class="profile-kpi-grid">
                <x-entity.kpi-stat :label="__('Users accounts')" :value="number_format($kpis['total_users']['value'])" accent>
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Tenant owners')" :value="number_format($kpis['tenant_owners']['value'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Staff accounts')" :value="number_format($kpis['staff_accounts']['value'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Super admins')" :value="number_format($kpis['super_admins']['value'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Verified')" :value="number_format($kpis['verified']['value'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Unverified')" :value="number_format($kpis['unverified']['value'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('New this month')" :value="number_format($kpis['new_this_month']['value'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Online now')" :value="number_format($kpis['online_now']['value'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728M8.464 15.536a5 5 0 010-7.072m7.072 0a5 5 0 010 7.072M13 12a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Active 7 days')" :value="number_format($kpis['active_7d']['value'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Active 30 days')" :value="number_format($kpis['active_30d']['value'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
            </div>

            <x-workspace.chart-grid :charts="$chartSpecs" pair />

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-700">{{ __('Largest tenants') }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Workspace users attached to each tenant owner.') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        @if ($topTenants === [])
                            <p class="p-6 text-sm text-slate-500">{{ __('No tenants in this data scope.') }}</p>
                        @else
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-4 py-3">{{ __('Tenant') }}</th>
                                        <th class="px-4 py-3">{{ __('Users') }}</th>
                                        <th class="px-4 py-3">{{ __('Businesses') }}</th>
                                        <th class="px-4 py-3">{{ __('Environment') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($topTenants as $tenant)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <p class="font-medium text-slate-900">{{ $tenant['name'] }}</p>
                                                <p class="text-xs text-slate-500">{{ $tenant['email'] }}</p>
                                            </td>
                                            <td class="px-4 py-3 tabular-nums text-slate-800">{{ number_format($tenant['users']) }}</td>
                                            <td class="px-4 py-3 tabular-nums text-slate-800">{{ number_format($tenant['businesses']) }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $tenant['environment'] === __('Test') ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                                    {{ $tenant['environment'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </section>

                <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-700">{{ __('Roles in use') }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Unique people holding each business role.') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        @if ($roleRows === [])
                            <p class="p-6 text-sm text-slate-500">{{ __('No business roles assigned yet.') }}</p>
                        @else
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-4 py-3">{{ __('Role') }}</th>
                                        <th class="px-4 py-3">{{ __('People') }}</th>
                                        <th class="px-4 py-3">{{ __('Share of users') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($roleRows as $row)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-slate-900">{{ $row['role'] }}</td>
                                            <td class="px-4 py-3 tabular-nums text-slate-800">{{ number_format($row['users']) }}</td>
                                            <td class="px-4 py-3 tabular-nums text-slate-600">{{ $row['share'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </section>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-700">{{ __('Recently joined') }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Newest workspace accounts in this scope.') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        @if ($recentUsers === [])
                            <p class="p-6 text-sm text-slate-500">{{ __('No recent registrations.') }}</p>
                        @else
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-4 py-3">{{ __('User') }}</th>
                                        <th class="px-4 py-3">{{ __('Type') }}</th>
                                        <th class="px-4 py-3">{{ __('Joined') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($recentUsers as $row)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <p class="font-medium text-slate-900">{{ $row['name'] }}</p>
                                                <p class="text-xs text-slate-500">{{ $row['email'] }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">{{ $row['kind'] }}</td>
                                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $row['joined'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </section>

                <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-700">{{ __('Quiet accounts') }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('No session in the last 30 days, or never signed in.') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        @if ($quietUsers === [])
                            <p class="p-6 text-sm text-slate-500">{{ __('Everyone in scope has been active this month.') }}</p>
                        @else
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-4 py-3">{{ __('User') }}</th>
                                        <th class="px-4 py-3">{{ __('Type') }}</th>
                                        <th class="px-4 py-3">{{ __('Last seen') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($quietUsers as $row)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <p class="font-medium text-slate-900">{{ $row['name'] }}</p>
                                                <p class="text-xs text-slate-500">{{ $row['email'] }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">{{ $row['kind'] }}</td>
                                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $row['last_seen'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.buchaChartColors = @json(config('bucha.chart'));
            window.superAdminChartSpecs = @json($chartSpecs);
        </script>
        @vite('resources/js/super-admin-charts.js')
    @endpush
</x-app-layout>
