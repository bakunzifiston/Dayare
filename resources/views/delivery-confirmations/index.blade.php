<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Delivery confirmations') }}
            </h2>
            <div class="flex flex-wrap items-center gap-2">
                @include('processor.partials.export-dropdown', [
                    'exportRoute' => 'delivery-confirmations.export',
                    'query' => request()->query(),
                ])
                <a href="{{ route('delivery-confirmations.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Confirm delivery') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="get" class="mb-6 rounded-xl border border-slate-200/60 bg-white p-4 shadow-sm grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
                <div>
                    <label for="confirmation_status" class="block text-xs font-medium text-slate-600">{{ __('Status') }}</label>
                    <select id="confirmation_status" name="confirmation_status" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach (\App\Models\DeliveryConfirmation::STATUSES as $s)
                            <option value="{{ $s }}" @selected(($filters['confirmation_status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="from" class="block text-xs font-medium text-slate-600">{{ __('From') }}</label>
                    <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm" />
                </div>
                <div>
                    <label for="to" class="block text-xs font-medium text-slate-600">{{ __('To') }}</label>
                    <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm" />
                </div>
                <div>
                    <label for="receiving_facility_id" class="block text-xs font-medium text-slate-600">{{ __('Receiving facility') }}</label>
                    <select id="receiving_facility_id" name="receiving_facility_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($facilities as $facility)
                            <option value="{{ $facility->id }}" @selected(($filters['receiving_facility_id'] ?? '') == $facility->id)>{{ $facility->facility_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-5 flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-bucha-primary text-white text-sm font-medium">{{ __('Filter') }}</button>
                    <a href="{{ route('delivery-confirmations.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">{{ __('Reset') }}</a>
                </div>
            </form>
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total confirmations') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Confirmed') }}" :value="$kpis['confirmed']" color="green" />
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($confirmations->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No delivery confirmations yet.') }}</p>
                    <a href="{{ route('delivery-confirmations.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                        {{ __('Confirm first delivery') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($confirmations as $c)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('delivery-confirmations.show', $c) }}" class="font-medium text-slate-900 hover:text-bucha-primary">
                                        {{ $c->transportTrip->vehicle_plate_number ?? '' }} — {{ $c->receiver_display }}
                                    </a>
                                    <p class="text-sm text-slate-500">
                                        {{ $c->received_date->format('d M Y') }} · {{ $c->receiver_name }} · {{ $c->received_quantity }} {{ $c->received_unit ?? 'units' }} · {{ ucfirst($c->confirmation_status) }}
                                        @if ($c->receiver_country)
                                            · {{ $c->receiver_country }}
                                        @endif
                                    </p>
                                    @if ($c->isInternationalExport())
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-sky-100 text-sky-800">{{ __('International') }}</span>
                                            @if (! $c->allExportDocumentsIssued())
                                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-900" title="{{ __('Export documents incomplete') }}">{{ __('Docs pending') }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('delivery-confirmations.show', $c) }}" class="text-sm text-bucha-primary hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('delivery-confirmations.edit', $c) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $confirmations->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
