@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.procurement.orders.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Purchase orders') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ $order->po_number }}</h2>
            </div>
            <x-butcher.status-badge :status="$order->status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-slate-500">{{ __('Supplier') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $order->supplier?->name }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Meat type') }}</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ $order->meat_type }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Requested weight') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $fmtKg($order->requested_weight_kg) }} kg</dd></div>
                    <div><dt class="text-slate-500">{{ __('Requested date') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $order->requested_date?->format('Y-m-d') }}</dd></div>
                    @if ($order->notes)
                        <div class="sm:col-span-2"><dt class="text-slate-500">{{ __('Notes') }}</dt><dd class="mt-1 text-slate-900">{{ $order->notes }}</dd></div>
                    @endif
                </dl>

                @if ($order->status !== \App\Models\ButcherPurchaseOrder::STATUS_DELIVERED)
                    <form method="post" action="{{ route('butcher.procurement.orders.status', $order) }}" class="mt-6 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <x-input-label for="status" :value="__('Update status')" />
                            <select id="status" name="status" class="mt-1 block rounded-lg border-gray-300 text-sm">
                                @foreach (\App\Models\ButcherPurchaseOrder::STATUSES as $status)
                                    <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                            {{ __('Save status') }}
                        </button>
                    </form>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('butcher.procurement.deliveries.create', ['purchase_order_id' => $order->id]) }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('Receive against this PO') }}
                    </a>
                </div>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Linked deliveries') }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($order->deliveries as $delivery)
                        <a href="{{ route('butcher.procurement.deliveries.show', $delivery) }}" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-slate-900">{{ $delivery->delivery_number }}</p>
                                <x-butcher.status-badge :status="$delivery->condition" />
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $fmtKg($delivery->received_weight_kg) }} kg · {{ $fmtMoney($delivery->total_cost) }} · {{ $delivery->received_at?->format('Y-m-d H:i') }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No deliveries linked to this purchase order yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
