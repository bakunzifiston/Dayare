<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Transport') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 px-6 py-8 sm:px-10 sm:py-9 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-bucha-primary">{{ __('Module') }}</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                    {{ __('Move certified product between facilities') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Each trip links a certificate to origin and destination sites, vehicle and driver details, and optional released cold storage. Confirm delivery when the load arrives.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3">
                    <a href="{{ route('transport-trips.create') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-bucha-primary text-white text-base font-bold shadow-lg shadow-bucha-primary/25 hover:bg-bucha-burgundy transition-colors ring-2 ring-bucha-primary/20">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Record trip') }}
                    </a>
                    <p class="text-sm text-slate-500 sm:max-w-xs sm:self-center">
                        {{ __('You need a certificate you can access; cold-room release is optional when stock is ready to leave storage.') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total trips') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $totalTrips }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Pending') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-700">{{ $pendingCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('In transit') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-700">{{ $inTransitCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Arrived') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-700">{{ $arrivedCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Completed') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $completedCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('With delivery confirmation') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $tripsWithDeliveryConfirmationCount }}</p>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('transport-trips.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('All trips') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Search the full list, open a trip, edit or remove.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open list') }} →</span>
                </a>
                <a href="{{ route('certificates.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Certificates') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Trips are created from certificates you can access.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Certification home') }} →</span>
                </a>
                <a href="{{ route('cold-rooms.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Cold room') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Release storage before linking a trip to warehouse stock.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Cold room home') }} →</span>
                </a>
                <a href="{{ route('delivery-confirmations.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Delivery confirmation') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Record receipt when the shipment reaches the destination.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open confirmations') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
