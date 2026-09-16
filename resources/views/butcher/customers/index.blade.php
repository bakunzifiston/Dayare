@php
    $filters = $filters ?? ['q' => '', 'tier' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'wholesale' => 0, 'with_credit' => 0, 'outstanding' => 0];
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.customers.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="customer_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input id="customer_q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Name, phone…') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="customer_tier" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Tier') }}</label>
                            <select id="customer_tier" name="tier" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['tier'] === 'all')>{{ __('All tiers') }}</option>
                                @foreach ($tiers as $tier)
                                    <option value="{{ $tier }}" @selected($filters['tier'] === $tier)>{{ ucfirst($tier) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.customers.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.customers.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Add customer') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Customers')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-users" />
                <x-butcher.kpi-card :label="__('Wholesale')" :value="(string) $kpis['wholesale']" tone="sky" icon="ti ti-building-store" />
                <x-butcher.kpi-card :label="__('With credit')" :value="(string) $kpis['with_credit']" tone="amber" icon="ti ti-credit-card" />
                <x-butcher.kpi-card :label="__('Outstanding')" :value="$fmtMoney($kpis['outstanding'])" tone="rose" icon="ti ti-cash" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Customers') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Retail, wholesale, and loyalty accounts.') }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count customer|:count customers', $customers->total(), ['count' => $customers->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Customer') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('Phone') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Tier') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5 text-right">{{ __('Credit limit') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Outstanding') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($customers as $customer)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-user text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $customer->name }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500 sm:hidden">{{ $customer->phone }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $customer->phone }}</td>
                                    <td class="px-4 py-3 sm:px-5 capitalize text-slate-700">{{ $customer->tier }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtMoney($customer->credit_limit) }}</td>
                                    <td @class(['px-4 py-3 sm:px-5 text-right tabular-nums', 'font-semibold text-amber-800' => (float) $customer->outstanding_balance > 0, 'text-slate-700' => (float) $customer->outstanding_balance <= 0])>
                                        {{ $fmtMoney($customer->outstanding_balance) }}
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <a href="{{ route('butcher.customers.edit', $customer) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <i class="ti ti-pencil text-sm leading-none" aria-hidden="true"></i>
                                            {{ __('Edit') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['tier'] !== 'all' ? __('No customers match your filters.') : __('No customers yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($customers->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $customers->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
