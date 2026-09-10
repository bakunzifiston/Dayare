@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $nextStatuses = match ($order->status) {
        \App\Models\ButcherPurchaseOrder::STATUS_DRAFT => [
            \App\Models\ButcherPurchaseOrder::STATUS_SENT,
            \App\Models\ButcherPurchaseOrder::STATUS_CANCELLED,
        ],
        \App\Models\ButcherPurchaseOrder::STATUS_SENT => [
            \App\Models\ButcherPurchaseOrder::STATUS_CONFIRMED,
            \App\Models\ButcherPurchaseOrder::STATUS_CANCELLED,
        ],
        \App\Models\ButcherPurchaseOrder::STATUS_CONFIRMED => [
            \App\Models\ButcherPurchaseOrder::STATUS_CANCELLED,
        ],
        default => [],
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.purchase-orders.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Purchase orders') }}</a>
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
                        <div class="sm:col-span-2"><dt class="text-slate-500">{{ __('Notes') }}</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $order->notes }}</dd></div>
                    @endif
                </dl>
            </section>

            @if ($nextStatuses !== [])
                <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Update status') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($nextStatuses as $status)
                            <form method="post" action="{{ route('butcher.purchase-orders.status', $order) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $status }}">
                                <button type="submit" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                    {{ __('Mark as :status', ['status' => str_replace('_', ' ', $status)]) }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! in_array($order->status, [\App\Models\ButcherPurchaseOrder::STATUS_DELIVERED, \App\Models\ButcherPurchaseOrder::STATUS_CANCELLED], true))
                <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Receive against this PO') }}</h3>
                        <a href="{{ route('butcher.receiving.create', ['purchase_order_id' => $order->id]) }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                            {{ __('Receive delivery') }}
                        </a>
                    </div>

                    @if ($linkableDeliveries->isNotEmpty())
                        <form method="post" action="{{ route('butcher.purchase-orders.link-delivery', $order) }}" class="space-y-3 border-t border-slate-100 pt-4">
                            @csrf
                            <x-input-label for="delivery_id" :value="__('Or link an existing unlinked delivery')" />
                            <div class="flex flex-wrap gap-3">
                                <select id="delivery_id" name="delivery_id" required class="block w-full max-w-md rounded-lg border-gray-300 text-sm">
                                    <option value="">{{ __('Select delivery…') }}</option>
                                    @foreach ($linkableDeliveries as $delivery)
                                        <option value="{{ $delivery->id }}">
                                            {{ $delivery->delivery_number }} — {{ ucfirst($delivery->meat_type) }} — {{ $fmtKg($delivery->received_weight_kg) }} kg
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                    {{ __('Link delivery') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </section>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Linked deliveries') }}</h3>
                <div class="mt-3 space-y-2 text-sm">
                    @forelse ($order->deliveries as $delivery)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2">
                            <a href="{{ route('butcher.receiving.show', $delivery) }}" class="font-medium text-bucha-primary hover:underline">{{ $delivery->delivery_number }}</a>
                            <span class="text-slate-600">{{ $fmtKg($delivery->received_weight_kg) }} kg · {{ $delivery->outlet?->name }}</span>
                        </div>
                    @empty
                        <p class="text-slate-500">{{ __('No deliveries linked yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
