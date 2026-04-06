<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Certificates') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 px-6 py-8 sm:px-10 sm:py-9 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-bucha-primary">{{ __('Certification') }}</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                    {{ __('Issue and manage certificates') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Certificates are issued per batch after post-mortem inspection with approved quantity greater than zero. Use the checklist below, then issue a certificate for eligible batches.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3">
                    <a href="{{ route('certificates.create') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-bucha-primary text-white text-base font-bold shadow-lg shadow-bucha-primary/25 hover:bg-bucha-burgundy transition-colors ring-2 ring-bucha-primary/20">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        {{ __('Issue certificate') }}
                    </a>
                    <p class="text-sm text-slate-500 sm:max-w-xs sm:self-center">
                        @if ($eligibleForCertificateCount > 0)
                            {{ trans_choice(':count batch is ready to certify.|:count batches are ready to certify.', $eligibleForCertificateCount, ['count' => $eligibleForCertificateCount]) }}
                        @else
                            {{ __('No eligible batches right now — complete post-mortem with approved quantity first.') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Ready to certify') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-700">{{ $eligibleForCertificateCount }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Post-mortem OK, no certificate yet') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Awaiting eligibility') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-700">{{ $waitingOnPostMortemCount }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ __('No cert yet — needs PM or approved qty') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Certificates') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $certificatesTotal }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Total issued') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Active') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $certificatesActive }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Status active') }}</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <a href="{{ route('certificates.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('All certificates') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Browse, open QR trace links, edit or revoke.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open list') }} →</span>
                </a>
                <a href="{{ route('post-mortem-inspections.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0V3m0 2v4m0-4h4m-4 0H9"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Post-mortem') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Approve quantity here before a batch can be certified.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open post-mortem') }} →</span>
                </a>
                <a href="{{ route('batches.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Batches') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Trace batch codes back to slaughter execution.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Batches home') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
