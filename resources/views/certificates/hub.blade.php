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
                       class="text-sm px-3 py-1.5 rounded border border-gray-800 bg-gray-900 hover:bg-gray-800 text-white">
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

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                <a href="{{ route('batches.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-box text-gray-400" aria-hidden="true"></i>
                    {{ __('Batches') }}
                </a>
                <a href="{{ route('post-mortem-inspections.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-activity text-gray-400" aria-hidden="true"></i>
                    {{ __('Post-mortem') }}
                </a>
                <a href="{{ route('transport-trips.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-truck text-gray-400" aria-hidden="true"></i>
                    {{ __('Transport') }}
                </a>
                <a href="{{ route('cold-rooms.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-snowflake text-gray-400" aria-hidden="true"></i>
                    {{ __('Cold rooms') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
