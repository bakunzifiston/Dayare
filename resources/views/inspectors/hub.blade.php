@php
    use App\Models\Inspector;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Inspectors') }}
            </h2>
            <a href="{{ route('inspectors.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Register inspector') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 px-6 py-8 sm:px-10 sm:py-9 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-bucha-primary">{{ __('Module') }}</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                    {{ __('Official inspectors per facility') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Register inspectors against slaughterhouses and other sites. Filter by facility or status, then open a profile to edit authorization and species scope.') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total inspectors') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($kpis['total']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Active') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-700">{{ number_format($kpis['active']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Expired status') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-700">{{ number_format($kpis['expired']) }}</p>
                </div>
            </div>

            <form method="get" action="{{ route('inspectors.hub') }}" class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    <div class="sm:col-span-2">
                        <label for="filter_search" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                        <input id="filter_search" type="search" name="search" value="{{ $filters['search'] }}"
                               placeholder="{{ __('Name, email, ID, or auth number') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                    </div>
                    <div>
                        <label for="filter_facility_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Facility') }}</label>
                        <select id="filter_facility_id" name="facility_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($facilities as $facility)
                                <option value="{{ $facility->id }}" @selected($filters['facility_id'] === (string) $facility->id)>{{ $facility->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_status" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                        <select id="filter_status" name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (Inspector::STATUSES as $statusOption)
                                <option value="{{ $statusOption }}" @selected($filters['status'] === $statusOption)>{{ ucfirst($statusOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 pb-2">
                            <input type="checkbox" name="auth_expired" value="1" @checked($filters['auth_expired'])
                                   class="rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary">
                            {{ __('Authorization expired') }}
                        </label>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                        {{ __('Apply') }}
                    </button>
                    @if (array_filter($filters, fn ($value) => $value !== false && $value !== ''))
                        <a href="{{ route('inspectors.hub') }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Clear') }}</a>
                    @endif
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($inspectors->isEmpty())
                    <div class="p-8 text-center text-slate-600">
                        <p class="mb-4">
                            @if (array_filter($filters, fn ($value) => $value !== false && $value !== ''))
                                {{ __('No inspectors match your filters.') }}
                            @else
                                {{ __('No inspectors registered yet.') }}
                            @endif
                        </p>
                        <a href="{{ route('inspectors.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Register first inspector') }}
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Inspector') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility') }}</th>
                                    <th class="px-4 py-3">{{ __('Authorization') }}</th>
                                    <th class="px-4 py-3">{{ __('Species') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($inspectors as $inspector)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('inspectors.show', $inspector) }}" class="font-medium text-slate-900 hover:text-bucha-primary">
                                                {{ $inspector->full_name }}
                                            </a>
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $inspector->national_id }} · {{ $inspector->email }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-slate-800">{{ $inspector->facility->facility_name ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <p class="font-mono text-xs text-slate-700">{{ $inspector->authorization_number ?? '—' }}</p>
                                            @if ($inspector->authorization_expiry_date)
                                                <p class="text-xs mt-0.5 {{ $inspector->isAuthorizationExpired() ? 'text-red-600' : 'text-slate-500' }}">
                                                    {{ __('Expires') }}: {{ $inspector->authorization_expiry_date->format('d M Y') }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $inspector->species_allowed ?: '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $inspector->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ ucfirst($inspector->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('inspectors.show', $inspector) }}" class="text-xs text-bucha-primary hover:underline">{{ __('View') }}</a>
                                            <span class="text-slate-300 mx-1">·</span>
                                            <a href="{{ route('inspectors.edit', $inspector) }}" class="text-xs text-slate-600 hover:underline">{{ __('Edit') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $inspectors->links() }}</div>
                @endif
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('slaughter-plans.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Slaughter planning') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Sessions reference an inspector for the facility.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Planning home') }} →</span>
                </a>
                <a href="{{ route('ante-mortem-inspections.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Ante-mortem') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Inspections are recorded per inspector and intake.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open ante-mortem') }} →</span>
                </a>
                <a href="{{ route('post-mortem-inspections.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0V3m0 2v4m0-4h4m-4 0H9"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Post-mortem') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Record inspection outcomes linked to the assigned inspector.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Post-mortem home') }} →</span>
                </a>
                <a href="{{ route('businesses.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Businesses') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Inspectors are registered against facilities under your businesses.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Businesses home') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
