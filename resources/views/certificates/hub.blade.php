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

                <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Certificates summary') }}">
                    <x-kpi-card stat compact color="slate" :title="$hubStats['certificates_label']" :value="number_format($hubStats['total_issued'])" glyph="certificate" />
                    <x-kpi-card stat compact color="bucha-success" :title="__('Active')" :value="number_format($hubStats['active'])" glyph="check" />
                    <x-kpi-card stat compact color="amber" :title="__('Expired')" :value="number_format($hubStats['expired'])" glyph="clock" />
                    <x-kpi-card stat compact color="bucha" :title="__('Ready to issue')" :value="number_format($hubStats['ready_to_issue'])" glyph="alert" />
                </section>

                @if ($readyExecutions->isNotEmpty())
                    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                        <p class="text-sm font-medium text-blue-900">
                            {{ __(':count slaughter execution(s) ready for certification', ['count' => $hubStats['ready_to_issue']]) }}
                        </p>
                        <ul class="mt-2 space-y-1.5">
                            @foreach ($readyExecutions as $execution)
                                <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="text-blue-950">
                                        {{ $execution->slaughter_time?->format('d M Y H:i') }}
                                        — {{ $execution->slaughterPlan?->facility?->facility_name ?? '—' }}
                                        ({{ $execution->slaughterPlan?->species ?? '—' }})
                                    </span>
                                    <a href="{{ route('certificates.create', ['slaughter_execution_id' => $execution->id]) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
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

                                <x-entity.profile-row :label="__('Animal')">
                                    @php
                                        $certAnimalIds = \App\Support\CertificateAnimalSelection::explicitCertificateAnimalIds($cert);
                                        $certTags = $cert->batch?->items
                                            ->filter(fn ($item) => $certAnimalIds->contains((int) $item->animal_intake_item_id))
                                            ->map(fn ($item) => $item->intakeItem?->ear_tag)
                                            ->filter()
                                            ->unique()
                                            ->values() ?? collect();
                                    @endphp
                                    {{ $certTags->isNotEmpty() ? $certTags->implode(', ') : '—' }}
                                </x-entity.profile-row>
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
                                    <x-entity.profile-highlight
                                        :value="$certTags->isNotEmpty() ? $certTags->implode(', ') : '—'"
                                        :label="__('Animal')"
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
