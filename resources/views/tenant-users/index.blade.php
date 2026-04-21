<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Users') }}
            </h2>
            <a href="{{ route('tenant-users.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                {{ __('Add user') }}
            </a>
        </div>
    </x-slot>

    @php
        $roleLabels = [
            'org_admin' => __('Org Admin'),
            'operations_manager' => __('Operations Manager'),
            'compliance_officer' => __('Compliance Officer'),
            'inspector' => __('Inspector'),
            'transport_manager' => __('Transport Manager'),
        ];
        $hasAlerts = ($alerts['users_without_roles'] ?? 0) > 0
            || ($alerts['pending_invitations'] ?? 0) > 0
            || ($alerts['role_conflicts'] ?? 0) > 0;
    @endphp

    <div class="py-6 lg:py-8">
        <div class="max-w-[1400px] mx-auto px-0 sm:px-0 space-y-6">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('info'))
                <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <section class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Users Roles KPI Dashboard') }}</h3>
                    <p class="text-xs text-bucha-muted mt-0.5">{{ __('Quick user and role health across your registered businesses.') }}</p>
                </div>

                <div class="rounded-bucha border border-slate-200 bg-white px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-slate-700">{{ __('Users Overview') }}</span>
                    </div>
                    <div class="relative w-full sm:w-72">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"></path>
                            </svg>
                        </span>
                        <input type="text" placeholder="{{ __('Search users') }}" class="w-full h-9 rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-slate-300 focus:ring-0">
                    </div>
                </div>

                <div class="rounded-bucha border border-slate-200 bg-white overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 divide-y sm:divide-y-0 xl:divide-x divide-slate-200">
                        <div class="px-5 py-4 min-h-[120px] flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-bucha-muted">{{ __('Total Users') }}</p>
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </span>
                            </div>
                            <p class="text-4xl font-bold leading-none text-slate-900">{{ $kpis['total_users'] ?? 0 }}</p>
                            <p class="text-xs text-slate-500">{{ __('All assigned users') }}</p>
                        </div>
                        <div class="px-5 py-4 min-h-[120px] flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-bucha-muted">{{ __('Active Users') }}</p>
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m20 6-11 11-5-5"/>
                                    </svg>
                                </span>
                            </div>
                            <p class="text-4xl font-bold leading-none text-slate-900">{{ $kpis['active_users'] ?? 0 }}</p>
                            <p class="text-xs text-emerald-600">{{ __('Verified accounts') }}</p>
                        </div>
                        <div class="px-5 py-4 min-h-[120px] flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-bucha-muted">{{ __('Recently Added') }}</p>
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                                    </svg>
                                </span>
                            </div>
                            <p class="text-4xl font-bold leading-none text-slate-900">{{ $kpis['recently_added_users'] ?? 0 }}</p>
                            <p class="text-xs text-slate-500">{{ __('Last 7 days') }}</p>
                        </div>
                        <div class="px-5 py-4 min-h-[120px]">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-bucha-muted">{{ __('Users by Role') }}</p>
                            <div class="mt-2 text-sm text-slate-700 leading-6">
                                @forelse (($kpis['users_by_role'] ?? []) as $role => $count)
                                    <span class="inline-block rounded bg-slate-100 px-2 py-0.5 text-xs mr-1 mb-1">
                                        {{ $roleLabels[$role] ?? ucwords(str_replace('_', ' ', (string) $role)) }}: {{ $count }}
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-500">{{ __('No role assignments yet') }}</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <div class="xl:col-span-9 bg-white overflow-hidden rounded-bucha border border-slate-200">
                    <div class="px-5 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('User Directory') }}</h3>
                            <p class="text-xs text-bucha-muted mt-0.5">{{ __('Role-focused user list across your registered businesses.') }}</p>
                        </div>
                        <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
                            <span class="rounded-md bg-white border border-slate-200 px-2 py-1">{{ __('Table') }}</span>
                            <span class="rounded-md bg-slate-100 px-2 py-1">{{ __('Roles') }}</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th class="px-4 py-3">{{ __('Name') }}</th>
                                    <th class="px-4 py-3">{{ __('Email') }}</th>
                                    <th class="px-4 py-3">{{ __('Role / Businesses') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($users as $u)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-2.5 font-medium text-slate-900">{{ $u->name }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $u->email }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">
                                            @foreach ($userBusinessRoles[$u->id] ?? [] as $br)
                                                <span class="inline-block rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs mr-1 mb-1">
                                                    {{ $br['business_name'] }} ({{ $roleLabels[$br['role']] ?? ucwords(str_replace('_', ' ', (string) $br['role'])) }})
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                            @if ($u->id === Auth::id())
                                                <span class="text-slate-400 text-xs">{{ __('You') }}</span>
                                                <span class="text-slate-300 mx-1">|</span>
                                                <a href="{{ route('profile.edit') }}" class="text-bucha-primary hover:text-bucha-burgundy text-xs font-medium">{{ __('Profile') }}</a>
                                            @else
                                                <a href="{{ route('tenant-users.edit', $u) }}" class="text-bucha-primary hover:text-bucha-burgundy text-xs font-medium">{{ __('Edit') }}</a>
                                                <span class="text-slate-300 mx-1">|</span>
                                                <form method="POST" action="{{ route('tenant-users.destroy', $u) }}" class="inline" onsubmit="return confirm('{{ __('Remove this user from your team? They will lose access to your businesses.') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('Remove') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="xl:col-span-3">
                    <div class="bg-white rounded-bucha border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Compliance Alerts') }}</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            @if ($hasAlerts)
                                @if (($alerts['users_without_roles'] ?? 0) > 0)
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                                        <p class="text-xs font-semibold text-amber-900">{{ __('Users without roles') }}</p>
                                        <p class="text-lg font-bold text-amber-900">{{ $alerts['users_without_roles'] }}</p>
                                    </div>
                                @endif
                                @if (($alerts['pending_invitations'] ?? 0) > 0)
                                    <div class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2">
                                        <p class="text-xs font-semibold text-sky-900">{{ __('Pending invitations') }}</p>
                                        <p class="text-lg font-bold text-sky-900">{{ $alerts['pending_invitations'] }}</p>
                                    </div>
                                @endif
                                @if (($alerts['role_conflicts'] ?? 0) > 0)
                                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2">
                                        <p class="text-xs font-semibold text-rose-900">{{ __('Role conflicts') }}</p>
                                        <p class="text-lg font-bold text-rose-900">{{ $alerts['role_conflicts'] }}</p>
                                    </div>
                                @endif
                                <p class="text-[11px] text-slate-500 pt-1">{{ __('Track and resolve role/access risks quickly.') }}</p>
                            @else
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                                    <p class="text-xs font-semibold text-emerald-900">{{ __('No active alerts') }}</p>
                                    <p class="text-xs text-emerald-700 mt-1">{{ __('Everything looks good.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
