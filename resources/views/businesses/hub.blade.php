@php
    use App\Models\Business;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Businesses') }}
            </h2>
            <a href="{{ route('businesses.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Register business') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 px-6 py-8 sm:px-10 sm:py-9 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-bucha-primary">{{ __('Workspace setup') }}</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                    {{ __('Manage your processor businesses') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Register slaughterhouses and storage sites under your workspace. Each business can have multiple facilities, inspectors, and operational records.') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total businesses') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($totalBusinesses) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Active') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $activeCount > 0 ? 'text-green-700' : 'text-slate-900' }}">
                        {{ number_format($activeCount) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Suspended') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $suspendedCount > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ number_format($suspendedCount) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total facilities') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($totalFacilities) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('With facilities') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($businessesWithFacilitiesCount) }}</p>
                </div>
            </div>

            <form method="get" action="{{ route('businesses.hub') }}" class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2">
                        <label for="filter_search" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                        <input id="filter_search" type="search" name="search" value="{{ $filters['search'] }}"
                               placeholder="{{ __('Business name, registration, or email') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                    </div>
                    <div>
                        <label for="filter_status" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                        <select id="filter_status" name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="{{ Business::STATUS_ACTIVE }}" @selected($filters['status'] === Business::STATUS_ACTIVE)>{{ __('Active') }}</option>
                            <option value="{{ Business::STATUS_SUSPENDED }}" @selected($filters['status'] === Business::STATUS_SUSPENDED)>{{ __('Suspended') }}</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Apply') }}
                        </button>
                        @if (array_filter($filters))
                            <a href="{{ route('businesses.hub') }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($businesses->isEmpty())
                    <div class="p-8 text-center text-slate-600">
                        <p class="mb-4">
                            @if (array_filter($filters))
                                {{ __('No businesses match your filters.') }}
                            @else
                                {{ __('You have not registered any business yet.') }}
                            @endif
                        </p>
                        <a href="{{ route('businesses.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Register your first business') }}
                        </a>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($businesses as $business)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('businesses.show', $business) }}" class="font-medium text-slate-900 hover:text-bucha-primary">
                                        {{ $business->business_name }}
                                    </a>
                                    <p class="text-sm text-slate-500">
                                        {{ $business->registration_number }} · {{ $business->email }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ $business->facilities_count }} {{ __('facility(ies)') }} · {{ ucfirst($business->status) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('businesses.show', $business) }}" class="text-sm text-bucha-primary hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('businesses.facilities.index', $business) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Facilities') }}</a>
                                    <a href="{{ route('businesses.edit', $business) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $businesses->links() }}</div>
                @endif
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('inspectors.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Inspectors') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Manage inspectors used across your facilities and inspections.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open inspectors') }} →</span>
                </a>
                <a href="{{ route('animal-intakes.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Animal intake') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Record origin at slaughterhouse facilities under your businesses.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Intake home') }} →</span>
                </a>
                <a href="{{ route('slaughter-plans.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Slaughter planning') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Schedule slaughter sessions from approved intakes.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Planning home') }} →</span>
                </a>
                <a href="{{ route('dashboard') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Dashboard') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Overview KPIs and shortcuts for your tenant.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open dashboard') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
