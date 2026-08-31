<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Finance') }}</span>
    </x-slot>

    @php
        $period = (string) ($kpiPeriod ?? 'all');
        $kpiCards = $kpiCards ?? [];
        $charts = $charts ?? [];
        $recentInvoices = $recentInvoices ?? [];
        $recentPayables = $recentPayables ?? [];
        $paymentTone = fn (string $state): string => match ($state) {
            'paid' => 'bg-emerald-50 text-emerald-800',
            'pending' => 'bg-amber-50 text-amber-900',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
        <script>
            window.buchaChartColors = @json(config('bucha.chart'));
            window.processorDashboardActiveRole = 'accountant';
            window.processorDashboardCharts = {
                accountant: @json($charts)
            };
        </script>
        @vite('resources/js/processor-dashboard.js')
    @endpush

    <div class="space-y-5">
        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Period and actions') }}">
            <div class="flex items-center gap-2 overflow-x-auto">
                <div class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="{{ __('Finance KPI time range') }}">
                    @foreach (['all' => __('All time'), 'day' => __('Day'), 'month' => __('Month'), 'year' => __('Year')] as $q => $title)
                        <a
                            href="{{ route('finance.dashboard', ['kpi_period' => $q]) }}"
                            @class([
                                'px-3 py-1.5 text-xs font-medium rounded-md transition',
                                'bg-bucha-primary text-white shadow-sm' => $period === $q,
                                'text-slate-600 hover:text-slate-900 hover:bg-white' => $period !== $q,
                            ])
                        >{{ $title }}</a>
                    @endforeach
                </div>
                <p class="shrink-0 text-xs text-slate-400">{{ $kpiPeriodLabel ?? __('All time') }}</p>
                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <a href="{{ route('finance.invoices.create') }}" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record sale') }}</a>
                    <a href="{{ route('finance.expenses.create') }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('New expense') }}</a>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-3" aria-label="{{ __('Key performance indicators') }}">
            @foreach ($kpiCards as $card)
                <x-kpi-card
                    stat
                    compact
                    :title="$card['label']"
                    :value="$card['value']"
                    :subtitle="$card['hint'] ?? null"
                    :color="$card['color'] ?? 'slate'"
                    :glyph="$card['glyph'] ?? 'clipboard'"
                    :href="$card['href'] ?? null"
                />
            @endforeach
        </section>

        @if (count($charts) > 0)
            <section aria-label="{{ __('Finance charts') }}">
                <x-workspace.chart-grid :charts="$charts" />
            </section>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ __('Open receivables') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Unpaid invoices first, then latest issued.') }}</p>
                    </div>
                    <a href="{{ route('finance.invoices.index') }}" class="text-xs font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('View AR') }}</a>
                </div>
                @if (count($recentInvoices) === 0)
                    <div class="px-4 py-10 text-center">
                        <p class="text-sm font-medium text-slate-800">{{ __('No invoices yet') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Record a sale to see it here.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">{{ __('Invoice') }}</th>
                                    <th class="px-4 py-2.5 text-right">{{ __('Amount') }}</th>
                                    <th class="px-4 py-2.5">{{ __('Payment') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentInvoices as $row)
                                    <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                                        <td class="px-4 py-2.5">
                                            <a href="{{ $row['href'] }}" class="font-medium text-slate-900 hover:text-bucha-primary">{{ $row['number'] }}</a>
                                            <p class="text-xs text-slate-500">{{ $row['party'] }} · {{ $row['date'] }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums font-semibold text-slate-900">{{ $row['amount'] }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $paymentTone($row['state']) }}">{{ $row['state_label'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ __('Open payables') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Unpaid bills first, then latest issued.') }}</p>
                    </div>
                    <a href="{{ route('finance.payables.index') }}" class="text-xs font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('View AP') }}</a>
                </div>
                @if (count($recentPayables) === 0)
                    <div class="px-4 py-10 text-center">
                        <p class="text-sm font-medium text-slate-800">{{ __('No payables yet') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Create a payable to see it here.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">{{ __('Payable') }}</th>
                                    <th class="px-4 py-2.5 text-right">{{ __('Amount') }}</th>
                                    <th class="px-4 py-2.5">{{ __('Payment') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentPayables as $row)
                                    <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                                        <td class="px-4 py-2.5">
                                            <a href="{{ $row['href'] }}" class="font-medium text-slate-900 hover:text-bucha-primary">{{ $row['number'] }}</a>
                                            <p class="text-xs text-slate-500">{{ $row['party'] }} · {{ $row['date'] }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums font-semibold text-slate-900">{{ $row['amount'] }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $paymentTone($row['state']) }}">{{ $row['state_label'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
