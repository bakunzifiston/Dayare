@php
    $business = $business ?? $businesses->first();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Butcher workspace') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Daily overview of sales, finance, and compliance.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if ($business)
                {{-- Greeting bar --}}
                <div class="rounded-xl border border-slate-200/80 bg-white px-5 py-4 shadow-sm">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">
                                {{ $greeting }}, {{ $business->business_name }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">{{ $today_date }}</p>
                        </div>
                    </div>
                </div>

                {{-- Today at a glance --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Today at a glance') }}</h3>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <x-butcher.kpi-card
                            :label="__('Sales count')"
                            :value="$today_at_glance['sales_count']['value']"
                            :trend="$today_at_glance['sales_count']['trend']"
                            :trend-text="$today_at_glance['sales_count']['trend_text']"
                            icon="ti ti-receipt"
                        />
                        <x-butcher.kpi-card
                            :label="__('Revenue')"
                            :value="$today_at_glance['revenue']['value']"
                            :trend="$today_at_glance['revenue']['trend']"
                            :trend-text="$today_at_glance['revenue']['trend_text']"
                            icon="ti ti-currency-franc"
                        />
                        <x-butcher.kpi-card
                            :label="__('Kg sold')"
                            :value="$today_at_glance['kg_sold']['value']"
                            :trend="$today_at_glance['kg_sold']['trend']"
                            :trend-text="$today_at_glance['kg_sold']['trend_text']"
                            icon="ti ti-scale"
                        />
                        <x-butcher.kpi-card
                            :label="__('Avg sale value')"
                            :value="$today_at_glance['avg_sale_value']['value']"
                            :trend="$today_at_glance['avg_sale_value']['trend']"
                            :trend-text="$today_at_glance['avg_sale_value']['trend_text']"
                            icon="ti ti-chart-bar"
                        />
                    </div>
                </section>

                {{-- Finance --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Finance') }}</h3>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <x-butcher.kpi-card
                            :label="__('Revenue this month')"
                            :value="$finance['revenue_mtd']['value']"
                            :subtext="$finance['revenue_mtd']['subtext']"
                            icon="ti ti-trending-up"
                        />
                        <x-butcher.kpi-card
                            :label="__('COGS')"
                            :value="$finance['cogs']['value']"
                            :subtext="$finance['cogs']['subtext']"
                            icon="ti ti-receipt-2"
                        />
                        <x-butcher.kpi-card
                            :label="__('Gross margin %')"
                            :value="$finance['gross_margin_pct']['value']"
                            :subtext="$finance['gross_margin_pct']['subtext']"
                            :color="$finance['gross_margin_pct']['color']"
                            icon="ti ti-percentage"
                        />
                        <x-butcher.kpi-card
                            :label="__('Credit outstanding')"
                            :value="$finance['credit_outstanding']['value']"
                            :subtext="$finance['credit_outstanding']['subtext']"
                            :color="$finance['credit_outstanding']['color']"
                            icon="ti ti-credit-card"
                        />
                    </div>
                </section>

                {{-- Compliance --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Compliance') }}</h3>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <x-butcher.kpi-card
                            :label="__('Hygiene log')"
                            :value="$compliance_kpis['hygiene_log']['value']"
                            :subtext="$compliance_kpis['hygiene_log']['subtext']"
                            :color="$compliance_kpis['hygiene_log']['color'] ?? null"
                            icon="ti ti-clipboard-check"
                        />
                        <x-butcher.kpi-card
                            :label="__('Staff health')"
                            :value="$compliance_kpis['staff_health']['value']"
                            :subtext="$compliance_kpis['staff_health']['subtext']"
                            :color="$compliance_kpis['staff_health']['color'] ?? null"
                            icon="ti ti-heartbeat"
                        />
                        <x-butcher.kpi-card
                            :label="__('Permits expiring')"
                            :value="$compliance_kpis['permits_expiring']['value']"
                            :subtext="$compliance_kpis['permits_expiring']['subtext']"
                            :color="$compliance_kpis['permits_expiring']['color']"
                            icon="ti ti-certificate"
                        />
                        <x-butcher.kpi-card
                            :label="__('Audit readiness')"
                            :value="$compliance_kpis['audit_readiness']['value']"
                            :subtext="$compliance_kpis['audit_readiness']['subtext']"
                            :color="$compliance_kpis['audit_readiness']['color']"
                            icon="ti ti-shield-check"
                        />
                    </div>
                </section>

                {{-- Alerts --}}
                <section class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Active alerts') }}</h3>
                    <ul class="mt-4 space-y-2">
                        @forelse ($alerts as $alert)
                            @php
                                $border = match ($alert['level']) {
                                    'danger' => 'border-l-red-500',
                                    'warning' => 'border-l-amber-500',
                                    default => 'border-l-blue-500',
                                };
                                $dot = match ($alert['level']) {
                                    'danger' => 'bg-red-500',
                                    'warning' => 'bg-amber-500',
                                    default => 'bg-blue-500',
                                };
                            @endphp
                            <li class="flex items-start gap-3 rounded-lg border border-slate-100 border-l-4 {{ $border }} bg-slate-50/80 px-3 py-2.5 text-sm text-slate-700">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
                                <span>{{ $alert['message'] }}</span>
                            </li>
                        @empty
                            <li class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-500">
                                {{ __('No active alerts.') }}
                            </li>
                        @endforelse
                    </ul>
                </section>

                {{-- Recent sales --}}
                <section class="rounded-xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-4 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent sales') }}</h3>
                        @if (($sales['open_orders']['value'] ?? '0') !== '0')
                            <span class="text-xs text-slate-500">
                                {{ __(':count open order(s)', ['count' => $sales['open_orders']['value']]) }}
                            </span>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">#</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Item') }}</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Amount (RWF)') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Payment') }}</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Time') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($recent_sales as $sale)
                                    <tr>
                                        <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{{ $sale['number'] }}</td>
                                        <td class="whitespace-nowrap px-5 py-3 text-slate-700">{{ $sale['customer'] }}</td>
                                        <td class="max-w-[12rem] truncate px-5 py-3 text-slate-600" title="{{ $sale['item'] }}">{{ $sale['item'] }}</td>
                                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums font-medium text-slate-900">{{ number_format($sale['amount'], 0) }}</td>
                                        <td class="whitespace-nowrap px-5 py-3">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium capitalize text-slate-700">
                                                {{ str_replace('_', ' ', $sale['payment']) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-600">{{ $sale['time'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-8 text-center text-slate-500">{{ __('No sales recorded yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @else
                <div class="rounded-xl border border-slate-200/80 bg-white p-8 text-center shadow-sm">
                    <p class="text-sm text-slate-600">{{ __('No butcher business is linked to this account yet.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
