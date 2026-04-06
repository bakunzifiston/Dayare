<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Batches') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 px-6 py-8 sm:px-10 sm:py-9 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-bucha-primary">{{ __('Module') }}</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                    {{ __('Batches from slaughter execution') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Create a batch after slaughter, then run post-mortem inspection. When approved quantity is greater than zero, you can issue a certificate for cold storage and transport.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3">
                    <a href="{{ route('batches.create') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-bucha-primary text-white text-base font-bold shadow-lg shadow-bucha-primary/25 hover:bg-bucha-burgundy transition-colors ring-2 ring-bucha-primary/20">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Create batch') }}
                    </a>
                    <p class="text-sm text-slate-500 sm:max-w-xs sm:self-center">
                        {{ __('Choose a slaughter execution and inspector. Batch codes are generated automatically.') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total batches') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $totalBatches }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Approved') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-700">{{ $approvedCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Pending') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-700">{{ $pendingCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Rejected') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-red-700">{{ $rejectedCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('With post-mortem') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $withPostMortemCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('With certificate') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $withCertificateCount }}</p>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('batches.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('All batches') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Search the full list, open a batch, edit or remove.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open list') }} →</span>
                </a>
                <a href="{{ route('slaughter-executions.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Slaughter execution') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Batches must be linked to a completed execution.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Execution home') }} →</span>
                </a>
                <a href="{{ route('post-mortem-inspections.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0V3m0 2v4m0-4h4m-4 0H9"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Post-mortem') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Record inspection and approved quantity per batch.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open post-mortem') }} →</span>
                </a>
                <a href="{{ route('certificates.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md sm:col-span-2 lg:col-span-1">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Certificates') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Issue certificates when post-mortem approves quantity.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Certification home') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
