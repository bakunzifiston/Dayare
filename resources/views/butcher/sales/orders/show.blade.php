@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.sales.orders.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Orders') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ $order->order_number }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $order->customer?->name }} · {{ $order->order_date?->toDateString() }}</p>
            </div>
            <x-butcher.status-badge :status="$order->status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha space-y-4">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">{{ __('Total') }}</dt><dd class="font-medium">{{ $fmtMoney($order->total_amount) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Deposit') }}</dt><dd class="font-medium">{{ $fmtMoney($order->deposit_paid) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Delivery') }}</dt><dd class="font-medium">{{ $order->delivery_date?->toDateString() ?? '—' }}</dd></div>
                    <div>
                        <dt class="text-slate-500">{{ __('Linked sale') }}</dt>
                        <dd class="font-medium">
                            @if ($order->sale)
                                <a href="{{ route('butcher.sales.show', $order->sale) }}" class="text-bucha-primary hover:underline">{{ $order->sale->sale_number }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>

                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2">{{ __('Product') }}</th>
                            <th class="py-2">{{ __('Qty') }}</th>
                            <th class="py-2 text-right">{{ __('Subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
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

                @if (! in_array($order->status, [\App\Models\ButcherOrder::STATUS_FULFILLED, \App\Models\ButcherOrder::STATUS_CANCELLED], true))
                    <form method="post" action="{{ route('butcher.sales.orders.status', $order) }}" class="flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Update status') }}</label>
                            <select name="status" class="mt-1 block rounded-lg border-gray-300 text-sm">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Save status') }}</button>
                    </form>
                @endif
            </section>

            @if ($order->isFulfillable())
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha space-y-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Fulfill order') }}</h3>
                    <p class="text-sm text-slate-600">{{ __('This creates one sale, deducts cut-output stock FIFO, and marks the order fulfilled.') }}</p>

                    @if (count($stockPreview))
                        <div class="overflow-x-auto rounded-lg border border-slate-100">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2">{{ __('Product') }}</th>
                                        <th class="px-3 py-2">{{ __('Ordered') }}</th>
                                        <th class="px-3 py-2">{{ __('Available cut stock') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stockPreview as $row)
                                        <tr class="border-t border-slate-100">
                                            <td class="px-3 py-2">{{ $row['product'] }}</td>
                                            <td class="px-3 py-2">{{ number_format($row['ordered_kg'], 2) }} kg</td>
                                            <td class="px-3 py-2 {{ $row['available_kg'] + 0.0005 < $row['ordered_kg'] ? 'text-red-700 font-semibold' : 'text-emerald-700' }}">
                                                {{ number_format($row['available_kg'], 2) }} kg
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <form method="post" action="{{ route('butcher.sales.orders.fulfill', $order) }}" class="space-y-4" onsubmit="return confirm(@json(__('Fulfill this order and create a sale?')))">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Outlet') }}</label>
                                <select name="outlet_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" @selected(old('outlet_id', $order->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Payment method') }}</label>
                                <select name="payment_method" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method }}" @selected(old('payment_method', 'credit') === $method)>{{ ucfirst($method) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Amount paid') }}</label>
                                <input type="number" step="0.01" min="0" name="amount_paid" value="{{ old('amount_paid', $order->deposit_paid) }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                <p class="mt-1 text-xs text-slate-500">{{ __('For credit, enter deposit already paid; remainder hits the customer balance.') }}</p>
                            </div>
                        </div>
                        @php
                            $canOverride = auth()->user()?->canButcherPermission(\App\Models\BusinessUser::PERMISSION_OVERRIDE_BUTCHER_BATCH_SAFETY, $business->id);
                        @endphp
                        @if ($canOverride)
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Safety override reason (if needed)') }}</label>
                                <textarea name="safety_override_reason" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" placeholder="{{ __('Required only when fulfilling from breached/expired stock') }}">{{ old('safety_override_reason') }}</textarea>
                            </div>
                        @endif
                        <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Fulfill & create sale') }}</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
