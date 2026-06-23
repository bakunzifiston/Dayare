<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Certificates') }}
            </h2>
            <a href="{{ route('certificates.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Issue certificate') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                @if (session('status'))
                    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif

                <form method="get" action="{{ route('certificates.hub') }}" class="hub-period-filter">
                    <div class="hub-period-filter__bar">
                        <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Certificate period') }}">
                            @foreach (['all' => __('All'), 'day' => __('Daily'), 'month' => __('Monthly'), 'year' => __('Yearly')] as $periodKey => $periodLabel)
                                <label class="hub-period-filter__toggle">
                                    <input type="radio" name="period" value="{{ $periodKey }}" @checked($filters['period'] === $periodKey)>
                                    <span>{{ $periodLabel }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="hub-period-filter__range">
                            <label for="filter_date_from" class="hub-period-filter__range-label">{{ __('From') }}</label>
                            <input id="filter_date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="hub-period-filter__input" aria-label="{{ __('Date from') }}">
                            <span class="hub-period-filter__sep" aria-hidden="true">–</span>
                            <label for="filter_date_to" class="hub-period-filter__range-label">{{ __('To') }}</label>
                            <input id="filter_date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="hub-period-filter__input" aria-label="{{ __('Date to') }}">
                        </div>

                        <div class="hub-period-filter__actions">
                            <button type="submit" class="hub-period-filter__apply">{{ __('Apply') }}</button>
                            @if ($filters['is_filtered'])
                                <a href="{{ route('certificates.hub') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                                <a href="{{ route('certificates.export', array_filter(['issued_from' => $filters['date_from'], 'issued_to' => $filters['date_to']])) }}"
                                   class="hub-period-filter__clear">{{ __('Export PDF') }}</a>
                            @endif
                        </div>
                    </div>
                    <p class="hub-period-filter__hint">{{ $filters['range_label'] }}</p>
                </form>

                <div class="profile-kpi-grid">
                    <x-entity.kpi-stat :label="$hubStats['certificates_label']" :value="number_format($hubStats['total_issued'])" accent>
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Active')" :value="number_format($hubStats['active'])" :accent="$hubStats['active'] > 0">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Expired')" :value="number_format($hubStats['expired'])" :accent="$hubStats['expired'] > 0">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Revoked')" :value="number_format($hubStats['revoked'])" :accent="$hubStats['revoked'] > 0">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat
                        :label="__('Ready to issue')"
                        :value="number_format($hubStats['ready_to_issue'])"
                        :accent="$hubStats['ready_to_issue'] > 0"
                    >
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                </div>

                @if ($readyBatches->isNotEmpty())
                    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                        <p class="text-sm font-medium text-blue-900">
                            {{ __(':count batch(es) ready for certification', ['count' => $hubStats['ready_to_issue']]) }}
                        </p>
                        <ul class="mt-2 space-y-1.5">
                            @foreach ($readyBatches as $batch)
                                <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="font-mono text-blue-950">{{ $batch->batch_code }}</span>
                                    <a href="{{ route('certificates.create', ['batch_id' => $batch->id]) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                        {{ __('Issue →') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($certificates->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">
                            {{ $filters['is_filtered'] ? __('No certificates issued in this period.') : __('No certificates issued yet.') }}
                        </p>
                        <a href="{{ route('certificates.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Issue first certificate') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($certificates as $cert)
                            @php
                                $facility = $cert->facility ?? $cert->batch?->slaughterExecution?->slaughterPlan?->facility;
                                $certLabel = $cert->certificate_number ?? 'CERT-'.($cert->batch?->batch_code ?? $cert->id);
                                $statusTone = $cert->isRevoked() ? 'danger' : ($cert->isExpired() ? 'warning' : 'active');
                                $initial = strtoupper(substr($certLabel, 0, 1));
                                $tripCount = $cert->transportTrips->count();
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>
                                    <a href="{{ route('certificates.show', $cert) }}">{{ $certLabel }}</a>
                                </x-slot:title>
                                <x-slot:subtitle>{{ $facility?->facility_name ?? '—' }}</x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$statusTone" :label="$cert->status_label" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Batch')">
                                    @if ($cert->batch)
                                        <a href="{{ route('batches.show', $cert->batch) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                            {{ $cert->batch->batch_code }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Issued')">{{ $cert->issued_at?->format('d M Y') ?? '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Expires')">
                                    @if ($cert->expiry_date)
                                        <span class="{{ $cert->isExpired() ? 'text-red-600 font-medium' : '' }}">
                                            {{ $cert->expiry_date->format('d M Y') }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Inspector')">{{ $cert->inspector?->full_name ?? '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Transport')">
                                    {{ $tripCount > 0 ? trans_choice(':count trip|:count trips', $tripCount, ['count' => $tripCount]) : '—' }}
                                </x-entity.profile-row>

                                <x-slot:highlights>
                                    <x-entity.profile-highlight
                                        :value="$cert->issued_at?->format('d M Y') ?? '—'"
                                        :label="__('Issued')"
                                    />
                                    <x-entity.profile-highlight
                                        :value="$cert->batch?->batch_code ?? '—'"
                                        :label="__('Batch')"
                                    />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('certificates.show', $cert)">{{ __('View') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('certificates.edit', $cert)">{{ __('Edit') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('certificates.qr', $cert)">{{ __('QR') }}</x-entity.text-action>
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $certificates->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
