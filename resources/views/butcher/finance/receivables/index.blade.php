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
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <a href="{{ route('butcher.finance.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                        <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                        {{ __('Finance') }}
                    </a>
                </div>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Total outstanding')" :value="$fmtMoney($report['totals']['outstanding'])" tone="bucha" icon="ti ti-cash" />
                <x-butcher.kpi-card :label="__('0–30 days')" :value="$fmtMoney($report['totals']['bucket_0_30'])" tone="emerald" icon="ti ti-clock" />
                <x-butcher.kpi-card :label="__('31–60 days')" :value="$fmtMoney($report['totals']['bucket_31_60'])" tone="amber" icon="ti ti-clock-exclamation" />
                <x-butcher.kpi-card :label="__('60+ days')" :value="$fmtMoney($report['totals']['bucket_60_plus'])" tone="rose" icon="ti ti-alert-circle" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Receivables aging') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Customer') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Outstanding') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5 text-right">{{ __('0–30') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5 text-right">{{ __('31–60') }}</th>
                                <th class="hidden lg:table-cell px-4 py-3 sm:px-5 text-right">{{ __('60+') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Aging') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($report['customers'] as $row)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-user text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $row['customer_name'] }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $row['phone'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-right font-semibold tabular-nums text-slate-900">{{ $fmtMoney($row['outstanding']) }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtMoney($row['bucket_0_30']) }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtMoney($row['bucket_31_60']) }}</td>
                                    <td @class(['hidden lg:table-cell px-4 py-3 sm:px-5 text-right tabular-nums', 'font-semibold text-red-700' => $row['bucket_60_plus'] > 0, 'text-slate-700' => $row['bucket_60_plus'] <= 0])>{{ $fmtMoney($row['bucket_60_plus']) }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $bucketLabel($row['aging_bucket']) }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <a href="{{ route('butcher.finance.receivables.show', $row['customer_id']) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <i class="ti ti-file-invoice text-sm leading-none" aria-hidden="true"></i>
                                            {{ __('Statement') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">{{ __('No customers with outstanding balances.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
