@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $customer = $statement['customer'];
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <a href="{{ route('butcher.finance.receivables.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Receivables') }}
                </a>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100">
                        <i class="ti ti-user text-base leading-none" aria-hidden="true"></i>
                    </span>
                    <p class="text-sm font-semibold text-slate-900">{{ $customer->name }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <x-butcher.kpi-card :label="__('Phone')" :value="$customer->phone ?: '—'" tone="sky" icon="ti ti-phone" />
                <x-butcher.kpi-card :label="__('Tier')" :value="ucfirst((string) $customer->tier)" tone="bucha" icon="ti ti-users" />
                <x-butcher.kpi-card :label="__('Credit limit')" :value="$fmtMoney($customer->credit_limit)" tone="amber" icon="ti ti-credit-card" />
                <x-butcher.kpi-card :label="__('Statement balance')" :value="$fmtMoney($statement['computed_balance'])" tone="rose" icon="ti ti-cash" />
            </div>

            @if (abs($statement['computed_balance'] - $statement['outstanding_balance']) > 0.05)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __('Statement running balance differs slightly from stored outstanding_balance. Investigate recent credit adjustments.') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Statement') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3 sm:px-5">{{ __('Date') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Description') }}</th>
                                <th class="px-4 py-3 text-right sm:px-5">{{ __('Debit') }}</th>
                                <th class="px-4 py-3 text-right sm:px-5">{{ __('Credit') }}</th>
                                <th class="px-4 py-3 text-right sm:px-5">{{ __('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($statement['lines'] as $line)
                                <tr class="border-b border-slate-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-700 sm:px-5">{{ $line['occurred_on'] }}</td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="font-medium text-slate-900">{{ $line['description'] }}</div>
                                        <div class="text-xs capitalize text-slate-500">{{ $line['type'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-700 sm:px-5">{{ (float) $line['debit'] > 0 ? $fmtMoney($line['debit']) : '—' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-700 sm:px-5">{{ (float) $line['credit'] > 0 ? $fmtMoney($line['credit']) : '—' }}</td>
                                    <td class="px-4 py-3 text-right font-medium tabular-nums text-slate-900 sm:px-5">{{ $fmtMoney($line['balance']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-500 sm:px-5">{{ __('No credit activity for this customer.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
