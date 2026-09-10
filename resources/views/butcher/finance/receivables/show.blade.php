@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $customer = $statement['customer'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.finance.receivables.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Receivables') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ $customer->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Customer statement as of :date', ['date' => $statement['as_of']]) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase text-slate-500">{{ __('Outstanding') }}</p>
                <p class="text-xl font-bold text-slate-900">{{ $fmtMoney($statement['outstanding_balance']) }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <dl class="grid grid-cols-2 gap-4 rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha text-sm sm:grid-cols-4">
                <div>
                    <dt class="text-slate-500">{{ __('Phone') }}</dt>
                    <dd class="mt-1 font-medium">{{ $customer->phone }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Tier') }}</dt>
                    <dd class="mt-1 font-medium capitalize">{{ $customer->tier }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Credit limit') }}</dt>
                    <dd class="mt-1 font-medium">{{ $fmtMoney($customer->credit_limit) }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Statement balance') }}</dt>
                    <dd class="mt-1 font-medium">{{ $fmtMoney($statement['computed_balance']) }}</dd>
                </div>
            </dl>

            @if (abs($statement['computed_balance'] - $statement['outstanding_balance']) > 0.05)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __('Statement running balance differs slightly from stored outstanding_balance. Investigate recent credit adjustments.') }}
                </div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-3">{{ __('Date') }}</th>
                            <th class="py-2 pr-3">{{ __('Description') }}</th>
                            <th class="py-2 pr-3 text-right">{{ __('Debit') }}</th>
                            <th class="py-2 pr-3 text-right">{{ __('Credit') }}</th>
                            <th class="py-2 text-right">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statement['lines'] as $line)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-3 whitespace-nowrap">{{ $line['occurred_on'] }}</td>
                                <td class="py-2 pr-3">
                                    <div>{{ $line['description'] }}</div>
                                    <div class="text-xs capitalize text-slate-500">{{ $line['type'] }}</div>
                                </td>
                                <td class="py-2 pr-3 text-right">{{ (float) $line['debit'] > 0 ? $fmtMoney($line['debit']) : '—' }}</td>
                                <td class="py-2 pr-3 text-right">{{ (float) $line['credit'] > 0 ? $fmtMoney($line['credit']) : '—' }}</td>
                                <td class="py-2 text-right font-medium">{{ $fmtMoney($line['balance']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">{{ __('No credit activity for this customer.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
