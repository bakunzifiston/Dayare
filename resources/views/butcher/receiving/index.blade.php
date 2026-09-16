@php
    $filters = $filters ?? ['q' => '', 'condition' => 'all', 'meat_type' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'received_kg' => 0, 'total_spend' => 0, 'rejected' => 0];
    $fmtKg = static fn ($v): string => number_format((float) $v, 1).' kg';
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.receiving.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label for="receiving_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input
                                id="receiving_q"
                                type="search"
                                name="q"
                                value="{{ $filters['q'] }}"
                                placeholder="{{ __('Delivery #, supplier…') }}"
                                class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary"
                            >
                        </div>
                        <div>
                            <label for="receiving_condition" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Condition') }}</label>
                            <select id="receiving_condition" name="condition" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['condition'] === 'all')>{{ __('All') }}</option>
                                @foreach ($conditions as $condition)
                                    <option value="{{ $condition }}" @selected($filters['condition'] === $condition)>{{ ucfirst($condition) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="receiving_meat_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat') }}</label>
                            <select id="receiving_meat_type" name="meat_type" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['meat_type'] === 'all')>{{ __('All types') }}</option>
                                @foreach ($meatTypes as $type)
                                    <option value="{{ $type }}" @selected($filters['meat_type'] === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.receiving.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a
                            href="{{ route('butcher.receiving.create') }}"
                            class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10"
                        >
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Receive delivery') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Deliveries')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-package" />
                <x-butcher.kpi-card :label="__('Received')" :value="$fmtKg($kpis['received_kg'])" tone="emerald" icon="ti ti-scale" />
                <x-butcher.kpi-card :label="__('Total spend')" :value="$fmtMoney($kpis['total_spend'])" tone="sky" icon="ti ti-cash" />
                <x-butcher.kpi-card :label="__('Rejected')" :value="(string) $kpis['rejected']" tone="amber" icon="ti ti-ban" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Delivery history') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Inbound stock from suppliers.') }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count delivery|:count deliveries', $deliveries->total(), ['count' => $deliveries->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Delivery') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Supplier') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5">{{ __('Outlet') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5 text-right">{{ __('Weight') }}</th>
                                <th class="hidden lg:table-cell px-4 py-3 sm:px-5 text-right">{{ __('Cost') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Condition') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($deliveries as $delivery)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-package text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium text-slate-900">{{ $delivery->delivery_number }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $delivery->received_at?->format('Y-m-d H:i') ?: '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $delivery->supplier?->name ?: '—' }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $delivery->outlet?->name ?: '—' }}</td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ number_format((float) $delivery->received_weight_kg, 2) }} kg</td>
                                    <td class="hidden lg:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtMoney($delivery->total_cost) }}</td>
                                    <td class="px-4 py-3 sm:px-5"><x-butcher.status-badge :status="$delivery->condition" /></td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <a
                                            href="{{ route('butcher.receiving.show', $delivery) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            <i class="ti ti-eye text-sm leading-none" aria-hidden="true"></i>
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['condition'] !== 'all' || $filters['meat_type'] !== 'all'
                                            ? __('No deliveries match your filters.')
                                            : __('No deliveries recorded yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($deliveries->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $deliveries->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
