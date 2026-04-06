<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Cold Room') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 px-6 py-8 sm:px-10 sm:py-9 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-bucha-primary">{{ __('Module') }}</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                    {{ __('Cold room storage & configuration') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Record certified batches in storage, define physical rooms and temperature standards, and link storage to rooms for monitoring.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3">
                    <a href="{{ route('cold-rooms.manage.index') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-bucha-primary text-white text-base font-bold shadow-lg shadow-bucha-primary/25 hover:bg-bucha-burgundy transition-colors ring-2 ring-bucha-primary/20">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        {{ __('Manage Cold Rooms') }}
                    </a>
                    <p class="text-sm text-slate-500 sm:max-w-xs sm:self-center">{{ __('Add or edit rooms, assign facilities, and attach temperature standards.') }}</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <a href="{{ route('cold-rooms.manage.index') }}" class="group flex flex-col rounded-2xl border-2 border-bucha-primary/35 bg-white p-6 shadow-md ring-1 ring-bucha-primary/10 transition hover:border-bucha-primary hover:shadow-lg">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-bucha-primary/15 text-bucha-primary">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Manage Cold Rooms') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600 leading-relaxed">{{ __('Register rooms at each storage facility and attach a temperature standard.') }}</p>
                    <p class="mt-4 text-3xl font-bold tabular-nums text-slate-900">{{ $roomCount }}</p>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Rooms') }}</p>
                    <span class="mt-5 inline-flex items-center text-sm font-semibold text-bucha-primary">{{ __('Open room list') }} →</span>
                </a>

                <a href="{{ route('warehouse-storages.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Storage records') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600 leading-relaxed">{{ __('List, create, and edit cold room storage. Log temperatures per storage record.') }}</p>
                    <p class="mt-4 text-3xl font-bold tabular-nums text-slate-900">{{ $storageCount }}</p>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total records') }}</p>
                    <span class="mt-5 inline-flex items-center text-sm font-semibold text-bucha-primary">{{ __('Open storage') }} →</span>
                </a>

                <a href="{{ route('cold-room-standards.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Temperature standards') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600 leading-relaxed">{{ __('Allowed °C ranges and tolerance before batches are marked at risk.') }}</p>
                    <p class="mt-4 text-3xl font-bold tabular-nums text-slate-900">{{ $standardCount }}</p>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Standards') }}</p>
                    <span class="mt-5 inline-flex items-center text-sm font-semibold text-bucha-primary">{{ __('Manage standards') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
