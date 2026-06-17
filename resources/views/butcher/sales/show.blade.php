@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $sale->sale_number }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $sale->sale_date?->toDateString() }} · {{ $sale->outlet?->name }}</p>
            </div>
            <x-butcher.status-badge :status="$sale->status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('butcher.sales.receipt', $sale) }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Download receipt') }}</a>
                <a href="{{ route('butcher.sales.invoice', $sale) }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Download invoice') }}</a>
                @if ($sale->isCancellable())
                    <form method="post" action="{{ route('butcher.sales.cancel', $sale) }}" onsubmit="return confirm(@json(__('Cancel this sale and restore stock?')))">
                        @csrf
                        <button type="submit" class="rounded-bucha border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">{{ __('Cancel sale') }}</button>
                    </form>
                @endif
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">{{ __('Customer') }}</dt><dd class="font-medium">{{ $sale->customer?->name ?? __('Walk-in') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Sold by') }}</dt><dd class="font-medium">{{ $sale->soldByUser?->name }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Payment') }}</dt><dd class="font-medium">{{ ucfirst($sale->payment_method) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Amount paid') }}</dt><dd class="font-medium">{{ $fmtMoney($sale->amount_paid) }}</dd></div>
                </dl>

                <table class="mt-6 min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2">{{ __('Item') }}</th>
                            <th class="py-2">{{ __('Qty') }}</th>
                            <th class="py-2 text-right">{{ __('Subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr class="border-b border-slate-100">
                                <td class="py-2">{{ $item->product?->name }}</td>
                                <td class="py-2">
                                    @if ((float) $item->quantity_kg > 0)
                                        {{ number_format((float) $item->quantity_kg, 2) }} kg
                                    @endif
                                    @if ($item->quantity_units)
                                        {{ $item->quantity_units }} {{ __('units') }}
                                    @endif
                                </td>
                                <td class="py-2 text-right font-medium">{{ $fmtMoney($item->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4 border-t border-slate-200 pt-4 space-y-1 text-sm text-right">
                    <p>{{ __('Subtotal') }}: {{ $fmtMoney($sale->subtotal) }}</p>
                    @if ((float) $sale->discount_amount > 0)
                        <p>{{ __('Discount') }}: −{{ $fmtMoney($sale->discount_amount) }}</p>
                    @endif
                    <p class="text-lg font-bold">{{ __('Total') }}: {{ $fmtMoney($sale->total_amount) }}</p>
                    @if ((float) $sale->change_given > 0)
                        <p class="text-slate-500">{{ __('Change') }}: {{ $fmtMoney($sale->change_given) }}</p>
                    @endif
                </div>

                @if ($sale->payments->isNotEmpty())
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <h4 class="text-xs font-semibold uppercase text-slate-500">{{ __('Split payments') }}</h4>
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($sale->payments as $payment)
                                <li>{{ ucfirst($payment->payment_method) }}: {{ $fmtMoney($payment->amount) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
