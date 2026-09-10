@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $tab = request()->query('tab', 'overview');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.inventory.batches.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Batches') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ $batch->batch_number }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-butcher.status-badge :status="$batch->status" />
                @if ($batch->hasTemperatureBreach())
                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">{{ __('Temperature Breach') }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($batch->hasTemperatureBreach())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ __('This batch is flagged for a temperature breach (:when). Cutting or selling from it requires a Manager/Owner override with a logged reason.', [
                        'when' => $batch->temperature_breach_at?->format('Y-m-d H:i') ?? __('unknown time'),
                    ]) }}
                </div>
            @endif
            <div class="flex gap-2 border-b border-slate-200 text-sm font-semibold">
                <a href="{{ route('butcher.inventory.batches.show', [$batch, 'tab' => 'overview']) }}" class="px-3 py-2 {{ $tab === 'overview' ? 'border-b-2 border-bucha-primary text-bucha-primary' : 'text-slate-500 hover:text-slate-800' }}">{{ __('Overview') }}</a>
                <a href="{{ route('butcher.inventory.batches.show', [$batch, 'tab' => 'movements']) }}" class="px-3 py-2 {{ $tab === 'movements' ? 'border-b-2 border-bucha-primary text-bucha-primary' : 'text-slate-500 hover:text-slate-800' }}">{{ __('Movement history') }}</a>
            </div>

            @if ($tab !== 'movements')
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
                                    <a href="{{ route('butcher.receiving.show', $batch->delivery) }}" class="font-medium text-bucha-primary hover:underline">{{ $batch->delivery->delivery_number }}</a>
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
            @else
                <section class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Movement history') }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Signed quantities: receipts and gains positive; disposals, consumption, and transfers out negative.') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('When') }}</th>
                                    <th class="px-4 py-3">{{ __('Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Qty (kg)') }}</th>
                                    <th class="px-4 py-3">{{ __('Before → After') }}</th>
                                    <th class="px-4 py-3">{{ __('Actor') }}</th>
                                    <th class="px-4 py-3">{{ __('Reference') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($movements as $movement)
                                    <tr>
                                        <td class="px-4 py-3">{{ $movement->occurred_at?->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3"><x-butcher.status-badge :status="$movement->type" /></td>
                                        <td class="px-4 py-3 font-medium {{ (float) $movement->quantity_kg < 0 ? 'text-red-700' : 'text-emerald-700' }}">
                                            {{ number_format((float) $movement->quantity_kg, 3) }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            {{ $movement->before_qty !== null ? number_format((float) $movement->before_qty, 3) : '—' }}
                                            →
                                            {{ $movement->after_qty !== null ? number_format((float) $movement->after_qty, 3) : '—' }}
                                        </td>
                                        <td class="px-4 py-3">{{ $movement->actor?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-xs text-slate-500">
                                            @if ($movement->reference_type)
                                                {{ class_basename($movement->reference_type) }} #{{ $movement->reference_id }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No ledger movements for this batch yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
