@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $summary = $summary ?? [];
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.inventory.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-md">
                        <x-butcher.outlet-filter :outlets="$outlets" :selected="$filterOutletId" class="w-full" />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('butcher.inventory.batches.index', array_filter(['outlet_id' => $filterOutletId])) }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('All batches') }}</a>
                        <a href="{{ route('butcher.inventory.temperatures.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Log temperature') }}</a>
                        <a href="{{ route('butcher.inventory.disposals.index') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Log disposal') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Batches in storage')" :value="(string) ($summary['batches_in_storage'] ?? 0)" tone="bucha" icon="ti ti-packages" />
                <x-butcher.kpi-card :label="__('Kg in storage')" :value="$fmtKg($summary['kg_in_storage'] ?? 0).' kg'" tone="emerald" icon="ti ti-scale" />
                <x-butcher.kpi-card :label="__('Expiring soon')" :value="(string) ($summary['expiring_soon'] ?? 0)" tone="amber" icon="ti ti-clock-exclamation" />
                <x-butcher.kpi-card :label="__('Temp breaches today')" :value="(string) ($summary['temp_breaches_today'] ?? 0)" tone="rose" icon="ti ti-temperature" />
            </div>

            @if (($summary['expired_batches'] ?? 0) > 0)
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ __(':count batch(es) marked expired. Review and dispose condemned stock.', ['count' => $summary['expired_batches']]) }}
                    <a href="{{ route('butcher.inventory.batches.index') }}" class="ml-1 font-semibold underline">{{ __('View batches') }}</a>
                </div>
            @endif

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('FIFO — oldest batches first') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Use oldest stock first.') }}</p>
                    </div>
                    <a href="{{ route('butcher.inventory.batches.index', array_filter(['outlet_id' => $filterOutletId])) }}" class="text-xs font-semibold text-bucha-primary hover:underline">{{ __('All batches') }}</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($summary['fifo_batches'] ?? [] as $batch)
                        <a href="{{ route('butcher.inventory.batches.show', $batch) }}" class="flex items-start gap-3 px-4 py-3 sm:px-5 hover:bg-slate-50/80">
                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                <i class="ti ti-package text-[1.15rem] leading-none"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="font-medium text-slate-900">{{ $batch->batch_number }}</p>
                                    <x-butcher.status-badge :status="$batch->status" />
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ ucfirst($batch->meat_type) }} · {{ $fmtKg($batch->remaining_weight_kg) }} kg · {{ __('Age') }} {{ $batch->ageInDays() }}d
                                    @if ($batch->isExpiringSoon()) · <span class="font-semibold text-amber-700">{{ __('Expiring soon') }}</span>@endif
                                </p>
                            </div>
                        </a>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No active batches in storage.') }}</p>
                    @endforelse
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent temperature logs') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($summary['recent_temperature_logs'] ?? [] as $log)
                            <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5 text-sm">
                                <span class="text-slate-700">{{ $log->storage_location }} · {{ $log->temperature_celsius }}°C</span>
                                @if ($log->is_breach)<x-butcher.status-badge status="rejected" />@endif
                            </div>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('No temperature logs yet.') }}</p>
                        @endforelse
                    </div>
                </section>
                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent disposals') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($summary['recent_disposals'] ?? [] as $disposal)
                            <div class="px-4 py-3 sm:px-5 text-sm">
                                <p class="font-medium text-slate-900">{{ $disposal->batch?->batch_number }} · {{ number_format((float) $disposal->weight_disposed_kg, 2) }} kg</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ ucfirst($disposal->reason) }}</p>
                            </div>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('No disposals recorded.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
