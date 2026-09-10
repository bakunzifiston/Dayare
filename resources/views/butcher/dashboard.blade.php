@php
    $business = $business ?? $businesses->first();
    $showOverview = request()->query('section') === 'overview';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Butcher workspace') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('What needs attention today — weekly and monthly figures are one click away.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($business && $today)
                <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-4 sm:px-5 shadow-sm">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">
                                {{ $greeting }}, {{ $business->business_name }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">{{ $today_date }}</p>
                        </div>
                        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 text-sm">
                            <a href="{{ route('butcher.dashboard') }}" @class([
                                'rounded-md px-3 py-1.5 font-semibold',
                                'bg-bucha-primary text-white' => ! $showOverview,
                                'text-slate-600 hover:bg-slate-50' => $showOverview,
                            ])>{{ __('Today') }}</a>
                            <a href="{{ route('butcher.dashboard', ['section' => 'overview']) }}" @class([
                                'rounded-md px-3 py-1.5 font-semibold',
                                'bg-bucha-primary text-white' => $showOverview,
                                'text-slate-600 hover:bg-slate-50' => ! $showOverview,
                            ])>{{ __('Overview') }}</a>
                        </div>
                    </div>
                </div>

                @if (! $showOverview)
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Today') }}</h3>
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <x-butcher.kpi-card
                                :label="__('Sales')"
                                :value="$today['sales_count']['value']"
                                :trend="$today['sales_count']['trend']"
                                :trend-text="$today['sales_count']['trend_text']"
                                icon="ti ti-receipt"
                            />
                            <x-butcher.kpi-card
                                :label="__('Revenue')"
                                :value="$today['revenue']['value']"
                                :trend="$today['revenue']['trend']"
                                :trend-text="$today['revenue']['trend_text']"
                                icon="ti ti-currency-franc"
                            />
                            <x-butcher.kpi-card
                                :label="__('Receiving')"
                                :value="$today['receiving']['value']"
                                :subtext="$today['receiving']['subtext']"
                                icon="ti ti-truck-delivery"
                            />
                            <x-butcher.kpi-card
                                :label="__('Open orders')"
                                :value="$today['open_orders']['value']"
                                :subtext="$today['open_orders']['subtext']"
                                icon="ti ti-clipboard-list"
                            />
                            <x-butcher.kpi-card
                                :label="__('Expiring soon')"
                                :value="$today['expiring_soon']['value']"
                                :subtext="$today['expiring_soon']['subtext']"
                                :color="$today['expiring_soon']['color']"
                                icon="ti ti-clock-exclamation"
                            />
                            <x-butcher.kpi-card
                                :label="__('Temp breaches')"
                                :value="$today['temperature_breaches']['value']"
                                :subtext="$today['temperature_breaches']['subtext']"
                                :color="$today['temperature_breaches']['color']"
                                icon="ti ti-temperature"
                            />
                            <x-butcher.kpi-card
                                :label="__('Hygiene log')"
                                :value="$today['hygiene_log']['value']"
                                :subtext="$today['hygiene_log']['subtext']"
                                :color="$today['hygiene_log']['color'] ?? null"
                                icon="ti ti-clipboard-check"
                            />
                            <x-butcher.kpi-card
                                :label="__('Credit outstanding')"
                                :value="$today['credit_outstanding']['value']"
                                :subtext="$today['credit_outstanding']['subtext']"
                                :color="$today['credit_outstanding']['color']"
                                icon="ti ti-credit-card"
                            />
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
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

                    <section class="rounded-xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent sales') }}</h3>
                            <span class="text-xs text-slate-500">
                                {{ __('Kg sold today') }}: {{ $today['kg_sold']['value'] }} · {{ __('Avg') }}: {{ $today['avg_sale_value']['value'] }}
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <th class="px-4 py-3 sm:px-5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">#</th>
                                        <th class="px-4 py-3 sm:px-5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</th>
                                        <th class="hidden sm:table-cell px-4 py-3 sm:px-5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Item') }}</th>
                                        <th class="px-4 py-3 sm:px-5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Amount') }}</th>
                                        <th class="px-4 py-3 sm:px-5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Time') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($recent_sales as $sale)
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5 font-medium text-slate-900">{{ $sale['number'] }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-slate-700">{{ $sale['customer'] }}</td>
                                            <td class="hidden sm:table-cell max-w-[12rem] truncate px-4 py-3 sm:px-5 text-slate-600" title="{{ $sale['item'] }}">{{ $sale['item'] }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-right tabular-nums font-medium text-slate-900">{{ number_format($sale['amount'], 0) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-right tabular-nums text-slate-600">{{ $sale['time'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">{{ __('No sales recorded yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @else
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Finance (month to date)') }}</h3>
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                            <x-butcher.kpi-card :label="__('Revenue MTD')" :value="$overview['finance']['revenue_mtd']['value']" :subtext="$overview['finance']['revenue_mtd']['subtext']" icon="ti ti-trending-up" />
                            <x-butcher.kpi-card :label="__('COGS')" :value="$overview['finance']['cogs']['value']" :subtext="$overview['finance']['cogs']['subtext']" icon="ti ti-receipt-2" />
                            <x-butcher.kpi-card :label="__('Gross margin %')" :value="$overview['finance']['gross_margin_pct']['value']" :subtext="$overview['finance']['gross_margin_pct']['subtext']" :color="$overview['finance']['gross_margin_pct']['color']" icon="ti ti-percentage" />
                            <x-butcher.kpi-card :label="__('Cash in')" :value="$overview['finance']['cash_in']['value']" :subtext="$overview['finance']['cash_in']['subtext']" icon="ti ti-arrow-down-left" />
                            <x-butcher.kpi-card :label="__('Cash out')" :value="$overview['finance']['cash_out']['value']" :subtext="$overview['finance']['cash_out']['subtext']" icon="ti ti-arrow-up-right" />
                        </div>
                    </section>

                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Yield & waste (30 days)') }}</h3>
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                            <x-butcher.kpi-card :label="__('Yield')" :value="$overview['operations']['yield_kg']['value']" :subtext="$overview['operations']['yield_kg']['subtext']" icon="ti ti-cut" />
                            <x-butcher.kpi-card :label="__('Waste')" :value="$overview['operations']['waste_kg']['value']" :subtext="$overview['operations']['waste_kg']['subtext']" icon="ti ti-trash" />
                            <x-butcher.kpi-card :label="__('Avg wastage')" :value="$overview['operations']['avg_wastage_pct']['value']" :subtext="$overview['operations']['avg_wastage_pct']['subtext']" icon="ti ti-chart-pie" />
                        </div>
                    </section>

                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Compliance overview') }}</h3>
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                            <x-butcher.kpi-card :label="__('Staff health')" :value="$overview['compliance']['staff_health']['value']" :subtext="$overview['compliance']['staff_health']['subtext']" :color="$overview['compliance']['staff_health']['color'] ?? null" icon="ti ti-heartbeat" />
                            <x-butcher.kpi-card :label="__('Permits expiring')" :value="$overview['compliance']['permits_expiring']['value']" :subtext="$overview['compliance']['permits_expiring']['subtext']" :color="$overview['compliance']['permits_expiring']['color']" icon="ti ti-certificate" />
                            <x-butcher.kpi-card :label="__('Audit readiness')" :value="$overview['compliance']['audit_readiness']['value']" :subtext="$overview['compliance']['audit_readiness']['subtext']" :color="$overview['compliance']['audit_readiness']['color']" icon="ti ti-shield-check" />
                        </div>
                    </section>
                @endif
            @else
                <div class="rounded-xl border border-slate-200/80 bg-white p-8 text-center shadow-sm">
                    <p class="text-sm text-slate-600">{{ __('No butcher business is linked to this account yet.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
