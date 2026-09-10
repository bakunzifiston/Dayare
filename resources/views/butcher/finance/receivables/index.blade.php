@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $bucketLabel = static function (string $bucket): string {
        return match ($bucket) {
            '0_30' => __('0–30 days'),
            '31_60' => __('31–60 days'),
            '60_plus' => __('60+ days'),
            default => $bucket,
        };
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.finance.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Finance') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Receivables') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Customer outstanding balances by sale-date aging. As of :date.', ['date' => $report['as_of']]) }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Total outstanding')" :value="$fmtMoney($report['totals']['outstanding'])" />
                <x-kpi-card stat :title="__('0–30 days')" :value="$fmtMoney($report['totals']['bucket_0_30'])" />
                <x-kpi-card stat :title="__('31–60 days')" :value="$fmtMoney($report['totals']['bucket_31_60'])" />
                <x-kpi-card stat :title="__('60+ days')" :value="$fmtMoney($report['totals']['bucket_60_plus'])" />
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Customer') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('Outstanding') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('0–30') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('31–60') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('60+') }}</th>
                            <th class="py-2 pr-4">{{ __('Aging') }}</th>
                            <th class="py-2 pr-4">{{ __('Oldest unpaid') }}</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['customers'] as $row)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">
                                    <div class="font-semibold text-slate-900">{{ $row['customer_name'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $row['phone'] }}</div>
                                </td>
                                <td class="py-3 pr-4 text-right font-semibold">{{ $fmtMoney($row['outstanding']) }}</td>
                                <td class="py-3 pr-4 text-right">{{ $fmtMoney($row['bucket_0_30']) }}</td>
                                <td class="py-3 pr-4 text-right">{{ $fmtMoney($row['bucket_31_60']) }}</td>
                                <td class="py-3 pr-4 text-right {{ $row['bucket_60_plus'] > 0 ? 'text-red-700 font-semibold' : '' }}">{{ $fmtMoney($row['bucket_60_plus']) }}</td>
                                <td class="py-3 pr-4">{{ $bucketLabel($row['aging_bucket']) }}</td>
                                <td class="py-3 pr-4">{{ $row['oldest_unpaid_sale_date'] ?? '—' }}</td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('butcher.finance.receivables.show', $row['customer_id']) }}" class="font-semibold text-bucha-primary hover:underline">{{ __('Statement') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500">{{ __('No customers with outstanding balances.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
