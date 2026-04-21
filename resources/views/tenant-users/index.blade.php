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

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('info'))
                <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <section>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('KPI Summary') }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ __('Quick user and role health across your registered businesses.') }}</p>
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Total Users') }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $kpis['total_users'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Active Users') }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $kpis['active_users'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Users by Role') }}</p>
                        <p class="mt-2 text-sm text-slate-700 leading-6">
                            @forelse (($kpis['users_by_role'] ?? []) as $role => $count)
                                <span class="inline-block rounded bg-slate-100 px-2 py-0.5 mr-1 mb-1">
                                    {{ $roleLabels[$role] ?? ucwords(str_replace('_', ' ', (string) $role)) }}: {{ $count }}
                                </span>
                            @empty
                                {{ __('No role assignments yet') }}
                            @endforelse
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Recently Added Users') }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $kpis['recently_added_users'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Last 7 days') }}</p>
                    </div>
                </div>
            </section>

            @if ($hasAlerts)
                <section x-data="{ dismissed: false }" x-show="!dismissed" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-amber-900">{{ __('Alerts / Notifications') }}</h3>
                            <ul class="mt-2 space-y-1 text-sm text-amber-800">
                                @if (($alerts['users_without_roles'] ?? 0) > 0)
                                    <li>• {{ __('Users without assigned roles: :count', ['count' => $alerts['users_without_roles']]) }}</li>
                                @endif
                                @if (($alerts['pending_invitations'] ?? 0) > 0)
                                    <li>• {{ __('Pending invitations (unverified accounts): :count', ['count' => $alerts['pending_invitations']]) }}</li>
                                @endif
                                @if (($alerts['role_conflicts'] ?? 0) > 0)
                                    <li>• {{ __('Role conflicts or missing permissions detected: :count', ['count' => $alerts['role_conflicts']]) }}</li>
                                @endif
                            </ul>
                        </div>
                        <button type="button" @click="dismissed = true" class="text-xs font-semibold text-amber-900 hover:text-amber-700">
                            {{ __('Dismiss') }}
                        </button>
                    </div>
                </section>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('User Directory') }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Role-focused user list across your registered businesses.') }}</p>
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
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $u->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $u->email }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @foreach ($userBusinessRoles[$u->id] ?? [] as $br)
                                            <span class="inline-block rounded bg-slate-100 px-2 py-0.5 text-xs mr-1 mb-1">
                                                {{ $br['business_name'] }} ({{ $roleLabels[$br['role']] ?? ucwords(str_replace('_', ' ', (string) $br['role'])) }})
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
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
        </div>
    </div>
</x-app-layout>
