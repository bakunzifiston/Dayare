<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Compliance & hygiene') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('RFA audit readiness — checklists, sanitation, permits, and staff health.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('butcher.compliance.report') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Audit report') }}</a>
                <a href="{{ route('butcher.compliance.hygiene.index') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Log hygiene') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($alerts['alert_total'] > 0)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __(':count compliance item(s) need attention.', ['count' => $alerts['alert_total']]) }}
                </div>
            @endif

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Missing logs today')" :value="$alerts['missing_hygiene_count']" :href="route('butcher.compliance.hygiene.index')" />
                <x-kpi-card stat :title="__('Health cards')" :value="$alerts['expiring_health_count']" :href="route('butcher.compliance.health.index')" />
                <x-kpi-card stat :title="__('Expiring permits')" :value="$alerts['expiring_permit_count']" :href="route('butcher.onboarding.permits')" />
                <x-kpi-card stat :title="__('Overdue sanitation')" :value="$alerts['overdue_sanitation_count']" :href="route('butcher.compliance.sanitation.index')" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Outlets without today\'s hygiene log') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($alerts['missing_hygiene_today'] as $outlet)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">{{ $outlet->name }}</div>
                        @empty
                            <p class="text-slate-500">{{ __('All outlets logged today.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Expiring staff health cards (30 days)') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($alerts['expiring_health_cards'] as $record)
                            <div class="flex justify-between rounded-lg border border-slate-200 px-3 py-2">
                                <span>{{ $record->user?->name }}</span>
                                <span class="text-amber-800 font-medium">{{ $record->expiry_date?->toDateString() }}</span>
                            </div>
                        @empty
                            <p class="text-slate-500">{{ __('No expiring health cards.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Permits expiring within 60 days') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($alerts['expiring_permits'] as $permit)
                            <div class="flex justify-between rounded-lg border border-slate-200 px-3 py-2">
                                <span>{{ str_replace('_', ' ', ucfirst($permit->permit_type)) }}</span>
                                <span class="font-medium">{{ $permit->expiry_date?->toDateString() }}</span>
                            </div>
                        @empty
                            <p class="text-slate-500">{{ __('All permits are current.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent failed / partial hygiene') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($alerts['recent_failed_hygiene'] as $log)
                            <a href="{{ route('butcher.compliance.hygiene.show', $log) }}" class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
                                {{ $log->outlet?->name }} · {{ $log->log_date?->toDateString() }}
                                <x-butcher.status-badge :status="$log->status" class="ml-2" />
                            </a>
                        @empty
                            <p class="text-slate-500">{{ __('No recent issues.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
