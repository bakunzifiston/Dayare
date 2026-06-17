@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.storage.batches.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Batches') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ $batch->batch_number }}</h2>
            </div>
            <x-butcher.status-badge :status="$batch->status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-slate-500">{{ __('Meat type') }}</dt><dd class="mt-1 font-medium capitalize">{{ $batch->meat_type }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Outlet') }}</dt><dd class="mt-1 font-medium">{{ $batch->outlet?->name }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Initial weight') }}</dt><dd class="mt-1 font-medium">{{ $fmtKg($batch->initial_weight_kg) }} kg</dd></div>
                    <div><dt class="text-slate-500">{{ __('Remaining') }}</dt><dd class="mt-1 font-medium">{{ $fmtKg($batch->remaining_weight_kg) }} kg</dd></div>
                    <div><dt class="text-slate-500">{{ __('Unit cost / kg') }}</dt><dd class="mt-1 font-medium">{{ $fmtMoney($batch->unit_cost_per_kg) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Storage location') }}</dt><dd class="mt-1 font-medium">{{ $batch->storage_location ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Received') }}</dt><dd class="mt-1 font-medium">{{ $batch->received_at?->format('Y-m-d H:i') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Best before') }}</dt><dd class="mt-1 font-medium">{{ $batch->best_before_date?->format('Y-m-d') }} ({{ $batch->daysUntilBestBefore() }}d)</dd></div>
                    @if ($batch->delivery)
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500">{{ __('Source delivery') }}</dt>
                            <dd class="mt-1">
                                <a href="{{ route('butcher.procurement.deliveries.show', $batch->delivery) }}" class="font-medium text-bucha-primary hover:underline">{{ $batch->delivery->delivery_number }}</a>
                                · {{ $batch->delivery->supplier?->name }}
                                @if ($batch->delivery->certificate_ref) · {{ __('Cert') }}: {{ $batch->delivery->certificate_ref }}@endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Temperature logs (location)') }}</h3>
                <div class="mt-4 space-y-2 text-sm">
                    @forelse ($temperatureLogs as $log)
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                            <span>{{ $log->logged_at?->format('Y-m-d H:i') }} · {{ $log->temperature_celsius }}°C · {{ $log->loggedByUser?->name }}</span>
                            @if ($log->is_breach)<span class="text-xs font-semibold text-red-700">{{ __('Breach') }}</span>@endif
                        </div>
                    @empty
                        <p class="text-slate-500">{{ __('No temperature readings for this location yet.') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Disposal history') }}</h3>
                <div class="mt-4 space-y-2 text-sm">
                    @forelse ($batch->disposalLogs as $disposal)
                        <div class="rounded-lg border border-slate-200 px-3 py-2">
                            <p class="font-medium">{{ $fmtKg($disposal->weight_disposed_kg) }} kg · {{ ucfirst($disposal->reason) }}</p>
                            <p class="text-xs text-slate-500">{{ $disposal->disposed_at?->format('Y-m-d H:i') }} · {{ $disposal->disposedByUser?->name }}</p>
                        </div>
                    @empty
                        <p class="text-slate-500">{{ __('No disposals for this batch.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
