<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Certificates') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-xl font-medium text-gray-900">{{ __('Certificates') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ __('Issue and manage meat inspection certificates. Each certificate links a batch to its post-mortem approval for traceability and transport.') }}
                    </p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('certificates.index') }}"
                       class="text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                        {{ __('View all') }}
                    </a>
                    <a href="{{ route('certificates.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                        {{ __('+ Issue certificate') }}
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total issued') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_issued']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Active') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['active'] > 0 ? 'text-green-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['active']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Expired') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['expired'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['expired']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Revoked') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['revoked'] > 0 ? 'text-red-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['revoked']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Ready to issue') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['ready_to_issue'] > 0 ? 'text-blue-700' : 'text-slate-900' }}"
                       @if ($hubStats['ready_to_issue'] > 0) title="{{ __('Batches with PM approved, cold room released, and no certificate yet') }}" @endif>
                        {{ number_format($hubStats['ready_to_issue']) }}
                    </p>
                </div>
            </div>

            @if ($readyBatches->isNotEmpty())
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium text-blue-800">
                            {{ __('Batches ready for certification (:count)', ['count' => $hubStats['ready_to_issue']]) }}
                        </p>
                        @if ($hubStats['ready_to_issue'] > $readyBatches->count())
                            <a href="{{ route('certificates.create') }}" class="text-xs text-blue-600 hover:underline">
                                {{ __('View all →') }}
                            </a>
                        @endif
                    </div>
                    <div class="space-y-2">
                        @foreach ($readyBatches as $batch)
                            <div class="flex items-center justify-between bg-white rounded border border-blue-100 px-3 py-2">
                                <div class="flex items-center gap-4 min-w-0 flex-wrap">
                                    <span class="font-mono text-xs text-gray-800">{{ $batch->batch_code }}</span>
                                    <span class="text-xs text-gray-500">
                                        {{ $batch->slaughterExecution->slaughterPlan->facility->facility_name ?? '—' }}
                                    </span>
                                    @if ($batch->postMortemInspection)
                                        <span class="text-xs text-green-700">
                                            {{ $batch->postMortemInspection->approved_quantity }} {{ __('approved') }}
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('certificates.create', ['batch_id' => $batch->id]) }}"
                                   class="text-xs text-white bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded shrink-0">
                                    {{ __('Issue →') }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                @foreach (['active', 'expired', 'revoked'] as $colStatus)
                    @php
                        $colCerts = $byStatus->get($colStatus, collect());
                        $colBadge = match ($colStatus) {
                            'active' => 'bg-green-100 text-green-700',
                            'expired' => 'bg-yellow-100 text-yellow-700',
                            'revoked' => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-700">{{ ucfirst($colStatus) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $colBadge }}">
                                {{ $colCerts->count() }}
                            </span>
                        </div>
                        @forelse ($colCerts->take(5) as $cert)
                            <div class="py-2 border-t border-gray-100 first:border-t-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-mono text-xs text-gray-800 truncate">
                                            {{ $cert->certificate_number ?? 'CERT-'.$cert->batch?->batch_code }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $cert->batch?->slaughterExecution->slaughterPlan->facility->facility_name ?? '—' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ __('Issued') }}: {{ $cert->issued_at?->format('d M Y') ?? '—' }}
                                        </p>
                                        @if ($cert->expiry_date)
                                            <p class="text-xs {{ $cert->isExpired() ? 'text-red-500' : 'text-gray-400' }} mt-0.5">
                                                {{ __('Expires') }}: {{ $cert->expiry_date->format('d M Y') }}
                                            </p>
                                        @endif
                                    </div>
                                    <span class="text-xs px-1.5 py-0.5 rounded-full {{ $cert->status_badge_class }} flex-shrink-0">
                                        {{ $cert->status_label }}
                                    </span>
                                </div>
                                <div class="flex gap-2 mt-1.5 flex-wrap">
                                    <a href="{{ route('certificates.show', $cert) }}"
                                       class="text-xs text-blue-600 hover:underline">{{ __('View') }}</a>
                                    <a href="{{ route('certificates.edit', $cert) }}"
                                       class="text-xs text-gray-500 hover:underline">{{ __('Edit') }}</a>
                                    @if ($cert->transportTrips->count() > 0)
                                        <span class="text-xs text-gray-400">
                                            {{ trans_choice(':count trip|:count trips', $cert->transportTrips->count(), ['count' => $cert->transportTrips->count()]) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 py-2">{{ __('No :status certificates.', ['status' => $colStatus]) }}</p>
                        @endforelse
                        @if ($colCerts->count() > 5)
                            <a href="{{ route('certificates.index', ['status' => $colStatus]) }}"
                               class="block mt-2 text-xs text-blue-600 hover:underline text-center">
                                {{ __('View all :count →', ['count' => $colCerts->count()]) }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-700">{{ __('Recent certificates') }}</p>
                    <a href="{{ route('certificates.index') }}"
                       class="text-xs text-blue-600 hover:underline">{{ __('View all →') }}</a>
                </div>
                @forelse ($recentCertificates as $cert)
                    @php
                        $dot = $cert->isRevoked() ? 'bg-red-400'
                            : ($cert->isExpired() ? 'bg-yellow-400' : 'bg-green-500');
                    @endphp
                    <div class="flex items-center gap-4 px-4 py-3 border-b border-gray-100 last:border-b-0">
                        <div class="w-2 h-2 rounded-full {{ $dot }} flex-shrink-0" aria-hidden="true"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-mono text-sm text-gray-800">
                                {{ $cert->certificate_number ?? 'CERT-'.$cert->batch?->batch_code }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $cert->batch?->slaughterExecution->slaughterPlan->facility->facility_name ?? '—' }}
                                · {{ $cert->issued_at?->format('d M Y') ?? '—' }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $cert->status_badge_class }}">
                                {{ $cert->status_label }}
                            </span>
                        </div>
                        <a href="{{ route('certificates.show', $cert) }}"
                           class="text-xs text-blue-600 hover:underline flex-shrink-0">{{ __('View') }}</a>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 px-4 py-6 text-center">
                        {{ __('No certificates issued yet.') }}
                        <a href="{{ route('certificates.create') }}" class="text-blue-600 hover:underline">
                            {{ __('Issue the first one →') }}
                        </a>
                    </p>
                @endforelse
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('certificates.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('All certificates') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Browse, open QR trace links, edit or revoke.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open list') }} →</span>
                </a>
                <a href="{{ route('post-mortem-inspections.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0V3m0 2v4m0-4h4m-4 0H9"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Post-mortem') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Approve quantity here before a batch can be certified.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Post-mortem home') }} →</span>
                </a>
                <a href="{{ route('batches.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Batches') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Trace batch codes back to slaughter execution.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Batches home') }} →</span>
                </a>
                <a href="{{ route('transport-trips.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Transport') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Record trips from active certificates to their destination.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Transport home') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
