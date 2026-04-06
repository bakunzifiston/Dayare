<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Slaughter execution') }}
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
                    {{ __('Record and track slaughter runs') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Link each execution to a slaughter session, then create batches for post-mortem inspection and certification.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3">
                    <a href="{{ route('slaughter-executions.create') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-bucha-primary text-white text-base font-bold shadow-lg shadow-bucha-primary/25 hover:bg-bucha-burgundy transition-colors ring-2 ring-bucha-primary/20">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Record execution') }}
                    </a>
                    <p class="text-sm text-slate-500 sm:max-w-xs sm:self-center">
                        {{ __('Choose an approved slaughter session, actual head count, and time. Status can reflect scheduling through completion.') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total executions') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $totalExecutions }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Completed') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-700">{{ $completedCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('In progress') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-700">{{ $inProgressCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Scheduled') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $scheduledCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Cancelled') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-500">{{ $cancelledCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Animals slaughtered (total)') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $totalAnimalsSlaughtered }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:col-span-2 lg:col-span-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Executions with batches') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $executionsWithBatchCount }}</p>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('slaughter-executions.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('All executions') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Search the full list, open a run, edit or remove.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open list') }} →</span>
                </a>
                <a href="{{ route('slaughter-plans.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Slaughter planning') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Sessions must exist before you record an execution.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Planning home') }} →</span>
                </a>
                <a href="{{ route('batches.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Batches') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Create batches tied to a completed execution.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Batches home') }} →</span>
                </a>
                <a href="{{ route('post-mortem-inspections.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0V3m0 2v4m0-4h4m-4 0H9"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Post-mortem') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Record inspection and approved quantity per batch.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open post-mortem') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
