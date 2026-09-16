<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('butcher.compliance.report') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Audit report') }}</a>
                    <a href="{{ route('butcher.compliance.sanitation.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Sanitation') }}</a>
                    <a href="{{ route('butcher.compliance.health.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Health cards') }}</a>
                    <a href="{{ route('butcher.compliance.hygiene.index') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                        {{ __('Log hygiene') }}
                    </a>
                </div>
            </section>

            @if ($alerts['alert_total'] > 0)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __(':count compliance item(s) need attention.', ['count' => $alerts['alert_total']]) }}
                </div>
            @endif

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Missing logs today')" :value="(string) $alerts['missing_hygiene_count']" tone="amber" icon="ti ti-clipboard-x" />
                <x-butcher.kpi-card :label="__('Health cards')" :value="(string) $alerts['expiring_health_count']" tone="rose" icon="ti ti-heartbeat" />
                <x-butcher.kpi-card :label="__('Expiring permits')" :value="(string) $alerts['expiring_permit_count']" tone="sky" icon="ti ti-file-certificate" />
                <x-butcher.kpi-card :label="__('Overdue sanitation')" :value="(string) $alerts['overdue_sanitation_count']" tone="bucha" icon="ti ti-droplet" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Outlets without today\'s hygiene log') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($alerts['missing_hygiene_today'] as $outlet)
                            <div class="px-4 py-3 sm:px-5 text-sm text-amber-900 bg-amber-50/40">{{ $outlet->name }}</div>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('All outlets logged today.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Expiring staff health cards (30 days)') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($alerts['expiring_health_cards'] as $record)
                            <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5 text-sm">
                                <span class="text-slate-700">{{ $record->user?->name }}</span>
                                <span class="font-medium text-amber-800">{{ $record->expiry_date?->toDateString() }}</span>
                            </div>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('No expiring health cards.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Permits expiring within 60 days') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($alerts['expiring_permits'] as $permit)
                            <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5 text-sm">
                                <span class="text-slate-700">{{ str_replace('_', ' ', ucfirst($permit->permit_type)) }}</span>
                                <span class="font-medium text-slate-900">{{ $permit->expiry_date?->toDateString() }}</span>
                            </div>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('All permits are current.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent failed / partial hygiene') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($alerts['recent_failed_hygiene'] as $log)
                            <a href="{{ route('butcher.compliance.hygiene.show', $log) }}" class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5 text-sm hover:bg-slate-50/80">
                                <span class="text-slate-700">{{ $log->outlet?->name }} · {{ $log->log_date?->toDateString() }}</span>
                                <x-butcher.status-badge :status="$log->status" />
                            </a>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('No recent issues.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
