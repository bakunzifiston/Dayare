@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $acceptedKg = $delivery->lines->sum(fn ($l) => (float) $l->accepted_weight_kg);
    $rejectedKg = $delivery->lines->sum(fn ($l) => (float) $l->rejected_weight_kg);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.receiving.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Receiving') }}</a>
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
                    <div><dt class="text-slate-500">{{ __('Received weight') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $fmtKg($delivery->received_weight_kg) }} kg</dd></div>
                    <div><dt class="text-slate-500">{{ __('Accepted / rejected') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $fmtKg($acceptedKg) }} / {{ $fmtKg($rejectedKg) }} kg</dd></div>
                    <div><dt class="text-slate-500">{{ __('Accepted cost') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $fmtMoney($delivery->total_cost) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Received at') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $delivery->received_at?->format('Y-m-d H:i') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Received by') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $delivery->receivedByUser?->name }}</dd></div>
                    @if ($delivery->purchaseOrder)
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500">{{ __('Purchase order') }}</dt>
                            <dd class="mt-1 font-medium text-slate-900">
                                <a href="{{ route('butcher.purchase-orders.show', $delivery->purchaseOrder) }}" class="text-bucha-primary hover:underline">{{ $delivery->purchaseOrder->po_number }}</a>
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

            <section class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Line outcomes') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Meat') }}</th>
                                <th class="px-4 py-3">{{ __('Received') }}</th>
                                <th class="px-4 py-3">{{ __('Accepted') }}</th>
                                <th class="px-4 py-3">{{ __('Rejected') }}</th>
                                <th class="px-4 py-3">{{ __('Outcome') }}</th>
                                <th class="px-4 py-3">{{ __('Result') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($delivery->lines as $line)
                                <tr>
                                    <td class="px-4 py-3 capitalize">{{ $line->meat_type }}</td>
                                    <td class="px-4 py-3">{{ $fmtKg($line->received_weight_kg) }} kg</td>
                                    <td class="px-4 py-3">{{ $fmtKg($line->accepted_weight_kg) }} kg</td>
                                    <td class="px-4 py-3">{{ $fmtKg($line->rejected_weight_kg) }} kg</td>
                                    <td class="px-4 py-3"><x-butcher.status-badge :status="$line->outcome" /></td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if ($line->inventoryBatch)
                                            {{ __('Batch :batch', ['batch' => $line->inventoryBatch->batch_number]) }}
                                        @endif
                                        @if ($line->rejection)
                                            @if ($line->inventoryBatch) · @endif
                                            {{ __('Rejection logged') }}
                                        @endif
                                        @if (! $line->inventoryBatch && ! $line->rejection)
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                                        {{ __('No line items — showing legacy delivery condition only.') }}
                                        <span class="ml-1 capitalize">({{ $delivery->condition }})</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($delivery->inventoryBatches->isNotEmpty())
                <section class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 space-y-1">
                    @foreach ($delivery->inventoryBatches as $batch)
                        <p>{{ __('Inventory batch :batch created with :kg kg available.', ['batch' => $batch->batch_number, 'kg' => $fmtKg($batch->remaining_weight_kg)]) }}</p>
                    @endforeach
                </section>
            @endif

            @if ($delivery->rejections->isNotEmpty())
                <section class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ __('Rejected quantity logged for :count line(s). Rejected weight does not create inventory.', ['count' => $delivery->rejections->count()]) }}
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
