@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.procurement.deliveries.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Deliveries') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ $delivery->delivery_number }}</h2>
            </div>
            <x-butcher.status-badge :status="$delivery->condition" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-slate-500">{{ __('Supplier') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $delivery->supplier?->name }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Outlet') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $delivery->outlet?->name }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Meat type') }}</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ $delivery->meat_type }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Received weight') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $fmtKg($delivery->received_weight_kg) }} kg</dd></div>
                    <div><dt class="text-slate-500">{{ __('Unit cost / kg') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $fmtMoney($delivery->unit_cost_per_kg) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Total cost') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $fmtMoney($delivery->total_cost) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Received at') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $delivery->received_at?->format('Y-m-d H:i') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Received by') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $delivery->receivedByUser?->name }}</dd></div>
                    @if ($delivery->purchaseOrder)
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500">{{ __('Purchase order') }}</dt>
                            <dd class="mt-1">
                                <a href="{{ route('butcher.procurement.orders.show', $delivery->purchaseOrder) }}" class="font-medium text-bucha-primary hover:underline">{{ $delivery->purchaseOrder->po_number }}</a>
                            </dd>
                        </div>
                    @endif
                    @if ($delivery->certificate_ref)
                        <div><dt class="text-slate-500">{{ __('Certificate ref') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $delivery->certificate_ref }}</dd></div>
                    @endif
                    @if ($delivery->certificate_issuer)
                        <div><dt class="text-slate-500">{{ __('Certificate issuer') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $delivery->certificate_issuer }}</dd></div>
                    @endif
                    @if ($delivery->notes)
                        <div class="sm:col-span-2"><dt class="text-slate-500">{{ __('Notes') }}</dt><dd class="mt-1 text-slate-900">{{ $delivery->notes }}</dd></div>
                    @endif
                </dl>
            </section>

            @if ($delivery->inventoryBatch)
                <section class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ __('Inventory batch :batch created with :kg kg available.', ['batch' => $delivery->inventoryBatch->batch_number, 'kg' => $fmtKg($delivery->inventoryBatch->remaining_weight_kg)]) }}
                </section>
            @elseif ($delivery->rejection)
                <section class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ __('Delivery rejected — logged in rejection register. No inventory was added.') }}
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
