@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Cold chain & storage') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Track batches, temperatures, FIFO age, and disposals.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('butcher.storage.temperatures.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Log temperature') }}</a>
                <a href="{{ route('butcher.storage.disposals.index') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Log disposal') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Batches in storage')" :value="$summary['batches_in_storage']" :href="route('butcher.storage.batches.index')" />
                <x-kpi-card stat :title="__('Kg in storage')" :value="$fmtKg($summary['kg_in_storage'])" :href="route('butcher.storage.batches.index')" />
                <x-kpi-card stat :title="__('Expiring soon')" :value="$summary['expiring_soon']" :href="route('butcher.storage.batches.index')" />
                <x-kpi-card stat :title="__('Temp breaches today')" :value="$summary['temp_breaches_today']" :href="route('butcher.storage.temperatures.index')" />
            </div>

            @if ($summary['expired_batches'] > 0)
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ __(':count batch(es) marked expired. Review and dispose condemned stock.', ['count' => $summary['expired_batches']]) }}
                    <a href="{{ route('butcher.storage.batches.index') }}" class="ml-1 font-semibold underline">{{ __('View batches') }}</a>
                </div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('FIFO — oldest batches first') }}</h3>
                    <a href="{{ route('butcher.storage.batches.index') }}" class="text-xs font-semibold text-bucha-primary hover:underline">{{ __('All batches') }}</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($summary['fifo_batches'] as $batch)
                        <a href="{{ route('butcher.storage.batches.show', $batch) }}" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900">{{ $batch->batch_number }}</p>
                                <x-butcher.status-badge :status="$batch->status" />
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ ucfirst($batch->meat_type) }} · {{ $fmtKg($batch->remaining_weight_kg) }} kg left · {{ __('Age') }} {{ $batch->ageInDays() }}d
                                @if ($batch->isExpiringSoon()) · <span class="font-semibold text-amber-700">{{ __('Expiring soon') }}</span>@endif
                            </p>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No active batches in storage.') }}</p>
                    @endforelse
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent temperature logs') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($summary['recent_temperature_logs'] as $log)
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                                <span>{{ $log->storage_location }} · {{ $log->temperature_celsius }}°C</span>
                                @if ($log->is_breach)<x-butcher.status-badge status="rejected" />@endif
                            </div>
                        @empty
                            <p class="text-slate-500">{{ __('No temperature logs yet. Log twice daily.') }}</p>
                        @endforelse
                    </div>
                </section>
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent disposals') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($summary['recent_disposals'] as $disposal)
                            <div class="rounded-lg border border-slate-200 px-3 py-2">
                                <p class="font-medium">{{ $disposal->batch?->batch_number }} · {{ number_format((float) $disposal->weight_disposed_kg, 2) }} kg</p>
                                <p class="text-xs text-slate-500">{{ ucfirst($disposal->reason) }}</p>
                            </div>
                        @empty
                            <p class="text-slate-500">{{ __('No disposals recorded.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
