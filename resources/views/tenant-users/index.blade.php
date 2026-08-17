<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Users') }}</h1>
            <div class="flex items-center gap-2">
                @if (Auth::user()?->isSuperAdmin() || Auth::user()?->ownsBusiness((int) Auth::user()?->activeProcessorBusinessId()))
                    <a href="{{ route('tenant-users.role-permissions.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4 7v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V7l-8-4Z"/>
                        </svg>
                        {{ __('Role access') }}
                    </a>
                @endif
                <a href="{{ route('tenant-users.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-bucha-primary px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-bucha-burgundy">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    {{ __('Add user') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $canCustomizeAccess = Auth::user()?->isSuperAdmin()
            || Auth::user()?->ownsBusiness((int) Auth::user()?->activeProcessorBusinessId());
        $roleLabels = [
            'org_admin' => __('Org Admin'),
            'operations_manager' => __('Operations Manager'),
            'compliance_officer' => __('Compliance Officer'),
            'inspector' => __('Inspector'),
            'transport_manager' => __('Transport Manager'),
            'accountant' => __('Accountant'),
        ];
    @endphp

    <div
        class="mx-auto max-w-7xl space-y-5 py-6 lg:py-8"
        x-data="{
            query: '',
            matches(name, email) {
                const q = this.query.trim().toLowerCase();
                if (!q) return true;
                return name.toLowerCase().includes(q) || email.toLowerCase().includes(q);
            }
        }"
    >
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif
        @if (session('info'))
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                {{ session('info') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="grid grid-cols-2 divide-x divide-y divide-slate-200 lg:grid-cols-4 lg:divide-y-0">
                <div class="px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('Total') }}</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-900">{{ $kpis['total_users'] ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Assigned users') }}</p>
                </div>
                <div class="px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('Active') }}</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-900">{{ $kpis['active_users'] ?? 0 }}</p>
                    <p class="mt-1 text-xs text-emerald-600">{{ __('Verified accounts') }}</p>
                </div>
                <div class="px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('Recent') }}</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-900">{{ $kpis['recently_added_users'] ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Last 7 days') }}</p>
                </div>
                <div class="px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('By role') }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @forelse (($kpis['users_by_role'] ?? []) as $role => $count)
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                {{ $roleLabels[$role] ?? ucwords(str_replace('_', ' ', (string) $role)) }}
                                <span class="ml-1 tabular-nums text-slate-900">{{ $count }}</span>
                            </span>
                        @empty
                            <span class="text-xs text-slate-500">{{ __('No roles yet') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Team members') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Users assigned to your processor businesses.') }}</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"></path>
                        </svg>
                    </span>
                    <input
                        type="search"
                        x-model="query"
                        placeholder="{{ __('Search by name or email') }}"
                        class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-bucha-primary focus:bg-white focus:ring-bucha-primary/20"
                    >
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80">
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3">{{ __('Name') }}</th>
                            <th class="px-5 py-3">{{ __('Email') }}</th>
                            <th class="px-5 py-3">{{ __('Role / Business') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $u)
                            <tr
                                class="transition hover:bg-slate-50/80"
                                x-show="matches(@js($u->name), @js($u->email))"
                                x-cloak
                            >
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                                            {{ strtoupper(mb_substr($u->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-900">{{ $u->name }}</p>
                                            @if ($u->id === Auth::id())
                                                <p class="text-[11px] font-medium text-slate-400">{{ __('You') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $u->email }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($userBusinessRoles[$u->id] ?? [] as $br)
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[11px] font-medium text-slate-600">
                                                {{ $roleLabels[$br['role']] ?? ucwords(str_replace('_', ' ', (string) $br['role'])) }}
                                                <span class="mx-1 text-slate-300">·</span>
                                                <span class="text-slate-500">{{ $br['business_name'] }}</span>
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400">{{ __('No role assigned') }}</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    @if ($u->id === Auth::id())
                                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                            {{ __('Profile') }}
                                        </a>
                                    @else
                                        <div class="inline-flex items-center gap-1">
                                            @if ($canCustomizeAccess)
                                                <a href="{{ route('tenant-users.user-permissions.index', $u) }}" class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                                    {{ __('Access') }}
                                                </a>
                                            @endif
                                            <a href="{{ route('tenant-users.edit', $u) }}" class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold text-bucha-primary transition hover:bg-bucha-primary/5 hover:text-bucha-burgundy">
                                                {{ __('Edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('tenant-users.destroy', $u) }}" class="inline" onsubmit="return confirm('{{ __('Remove this user from your team? They will lose access to your businesses.') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 hover:text-red-700">
                                                    {{ __('Remove') }}
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center">
                                    <p class="text-sm font-medium text-slate-900">{{ __('No users yet') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Add a team member to start assigning roles.') }}</p>
                                    <a href="{{ route('tenant-users.create') }}" class="mt-4 inline-flex items-center rounded-lg bg-bucha-primary px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-bucha-burgundy">
                                        {{ __('Add user') }}
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
