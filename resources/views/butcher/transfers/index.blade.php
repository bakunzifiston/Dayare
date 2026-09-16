@php
    $filters = $filters ?? ['q' => ''];
    $kpis = $kpis ?? ['total' => 0, 'quantity_kg' => 0, 'today' => 0, 'outlets' => 0];
    $fmtKg = static fn ($v): string => number_format((float) $v, 2).' kg';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.transfers.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-xl">
                        <div>
                            <label for="transfer_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input id="transfer_q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Outlet, batch…') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.transfers.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.transfers.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('New transfer') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Transfers')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-arrows-exchange" />
                <x-butcher.kpi-card :label="__('Moved')" :value="$fmtKg($kpis['quantity_kg'])" tone="sky" icon="ti ti-scale" />
                <x-butcher.kpi-card :label="__('Today')" :value="(string) $kpis['today']" tone="emerald" icon="ti ti-calendar" />
                <x-butcher.kpi-card :label="__('Active outlets')" :value="(string) $kpis['outlets']" tone="amber" icon="ti ti-building-store" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Stock transfers') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Moves between outlets.') }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count transfer|:count transfers', $transfers->total(), ['count' => $transfers->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Transfer') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('From') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('To') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5">{{ __('Batch') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Qty') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($transfers as $transfer)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-arrows-exchange text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium text-slate-900">{{ $transfer->transferred_at?->format('Y-m-d H:i') ?: '—' }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500 md:hidden">{{ $transfer->batch?->batch_number }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $transfer->fromOutlet?->name ?: '—' }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $transfer->toOutlet?->name ?: '—' }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-slate-700">
                                        @if ($transfer->batch)
                                            <a href="{{ route('butcher.inventory.batches.show', $transfer->batch) }}" class="font-medium text-bucha-primary hover:underline">{{ $transfer->batch->batch_number }}</a>
                                            @if ($transfer->destinationBatch)
                                                <span class="text-slate-400">→</span>
                                                <a href="{{ route('butcher.inventory.batches.show', $transfer->destinationBatch) }}" class="font-medium text-bucha-primary hover:underline">{{ $transfer->destinationBatch->batch_number }}</a>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtKg($transfer->quantity_kg) }}</td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $transfer->transferredByUser?->name ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' ? __('No transfers match your filters.') : __('No transfers yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($transfers->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $transfers->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
