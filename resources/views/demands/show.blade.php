<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Demand') }} — {{ $demand->demand_number }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('demands.edit', $demand) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('demands.destroy', $demand) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this demand?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">{{ __('Delete') }}</button>
                </form>
                <a href="{{ route('demands.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Back to list') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Demand number') }}</dt><dd class="mt-1 text-sm text-slate-900 font-medium">{{ $demand->demand_number }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Title') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->title }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Business') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->business?->business_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Status') }}</dt><dd class="mt-1"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                        @if($demand->status === 'fulfilled') bg-emerald-50 text-emerald-700
                        @elseif($demand->status === 'confirmed' || $demand->status === 'in_progress') bg-blue-50 text-blue-700
                        @elseif($demand->status === 'cancelled') bg-slate-100 text-slate-600
                        @else bg-slate-100 text-slate-700 @endif">{{ \App\Models\Demand::STATUSES[$demand->status] ?? $demand->status }}</span></dd></div>
                    @if ($demand->fulfilledByDelivery)
                    <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Fulfilled by delivery') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('delivery-confirmations.show', $demand->fulfilledByDelivery) }}" class="text-bucha-primary hover:underline">{{ __('Delivery') }} — {{ $demand->fulfilledByDelivery->received_date?->format('d M Y') }} ({{ $demand->fulfilledByDelivery->receiver_display }})</a></dd></div>
                    @endif
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Destination') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->destination_display }}</dd></div>
                    @if ($demand->client_id && $demand->client)
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Client') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('clients.show', $demand->client) }}" class="text-bucha-primary hover:underline">{{ $demand->client->display_name }}</a></dd></div>
                    @endif
                    @if ($demand->destination_facility_id && $demand->destinationFacility)
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Facility') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('businesses.facilities.show', [$demand->destinationFacility->business, $demand->destinationFacility]) }}" class="text-bucha-primary hover:underline">{{ $demand->destinationFacility->facility_name }}</a></dd></div>
                    @endif
                    @if ($demand->contract_id && $demand->contract)
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Contract') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('contracts.show', $demand->contract) }}" class="text-bucha-primary hover:underline">{{ $demand->contract->contract_number }}</a></dd></div>
                    @endif
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Species') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->species }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Quantity') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->quantity }} {{ $demand->quantity_unit_label }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Requested delivery date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->requested_delivery_date?->format('d M Y') ?? '—' }}</dd></div>
                    @if ($demand->product_description)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Product description') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->product_description }}</dd></div>@endif
                    @if ($demand->isExternalClient())
                        @if ($demand->client_name || $demand->client_company)<div><dt class="text-sm font-medium text-slate-500">{{ __('Client name') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->client_company ?: $demand->client_name }}</dd></div>@endif
                        @if ($demand->client_country)<div><dt class="text-sm font-medium text-slate-500">{{ __('Country') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->client_country }}</dd></div>@endif
                        @if ($demand->client_contact)<div><dt class="text-sm font-medium text-slate-500">{{ __('Contact') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->client_contact }}</dd></div>@endif
                        @if ($demand->client_address)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Address') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->client_address }}</dd></div>@endif
                    @endif
                    @if ($demand->notes)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Notes') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $demand->notes }}</dd></div>@endif
                </dl>
            </div>

            @php $fulfill = $demand->getFulfillmentInfo(); @endphp
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h3 class="text-sm font-semibold text-slate-800 mb-3">{{ __('Can we fulfill this demand?') }}</h3>
                <div class="flex items-start gap-4">
                    @if ($fulfill['can_fulfill'])
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-emerald-50 text-emerald-700">{{ __('Yes') }}</span>
                    @else
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-amber-50 text-amber-700">{{ __('No') }}</span>
                    @endif
                    <div class="text-sm text-slate-700 flex-1">
                        <p>{{ $fulfill['message'] }}</p>
                        <p class="mt-1 text-slate-500">
                            {{ __('Required') }}: {{ number_format($fulfill['required_quantity'], 2) }} {{ $fulfill['unit'] }}
                            · {{ __('Compliant in cold room') }}: {{ number_format($fulfill['compliant_quantity'], 2) }} {{ $fulfill['unit'] }}
                            @if ($fulfill['total_warehouse_quantity'] > $fulfill['compliant_quantity'])
                                · <span class="text-amber-600">{{ __('Non-compliant (expired/revoked cert)') }}: {{ number_format($fulfill['total_warehouse_quantity'] - $fulfill['compliant_quantity'], 2) }} {{ $fulfill['unit'] }}</span>
                            @endif
                            @if ($fulfill['short_by'] > 0)
                                · {{ __('Short by') }} {{ number_format($fulfill['short_by'], 2) }} {{ $fulfill['unit'] }}
                            @endif
                        </p>
                        <p class="mt-1 text-slate-500">
                            <span class="font-medium">{{ __('Compliance') }}:</span>
                            @if ($fulfill['compliant_quantity'] >= $fulfill['required_quantity'] && $fulfill['required_quantity'] > 0)
                                <span class="text-emerald-600">{{ __('Demand can be met with compliant stock only (active certificate, not expired).') }}</span>
                            @elseif ($fulfill['total_warehouse_quantity'] > 0 && $fulfill['compliant_quantity'] === 0)
                                <span class="text-amber-600">{{ __('Stock in cold room has no valid certificate (expired/revoked).') }}</span>
                            @elseif ($fulfill['total_warehouse_quantity'] > $fulfill['compliant_quantity'])
                                <span class="text-amber-600">{{ __('Part of cold room stock is non-compliant; only compliant quantity counts for fulfillment.') }}</span>
                            @else
                                <span class="text-slate-600">{{ __('No stock in cold room for this species.') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
