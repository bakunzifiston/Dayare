<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Delivery confirmation') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $confirmation->transportTrip->vehicle_plate_number ?? '—' }}</p>
                    <p class="text-xs text-slate-500">{{ $confirmation->receiver_name }} · {{ ucfirst($confirmation->confirmation_status) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('delivery-confirmations.edit', $confirmation) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <a href="{{ route('transport-trips.show', $confirmation->transportTrip) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('View trip') }}</a>
                    <a href="{{ route('delivery-confirmations.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Transport trip') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('transport-trips.show', $confirmation->transportTrip) }}" class="text-bucha-primary hover:underline">
                            {{ $confirmation->transportTrip->vehicle_plate_number }} — {{ $confirmation->transportTrip->driver_name }}
                        </a>
                        <span class="ml-2">@include('processor.partials.trip-status-badge', ['status' => $confirmation->transportTrip->status])</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Certificate') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        @if ($confirmation->transportTrip?->certificate)
                            <a href="{{ route('certificates.show', $confirmation->transportTrip->certificate) }}" class="text-bucha-primary hover:underline">
                                {{ $confirmation->transportTrip->certificate->certificate_number }}
                            </a>
                            @if ($confirmation->transportTrip->certificate->batch?->batch_code)
                                <span class="text-slate-500">({{ $confirmation->transportTrip->certificate->batch->batch_code }})</span>
                            @endif
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Trip destination') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $confirmation->transportTrip->destination_display ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Receiving at') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        @if ($confirmation->receivingFacility)
                            {{ $confirmation->receivingFacility->facility_name }}
                            <span class="text-xs text-slate-500">({{ __('legacy record') }})</span>
                        @else
                            {{ $confirmation->receiver_name }}{!! $confirmation->receiver_country ? ' (' . e($confirmation->receiver_country) . ')' : '' !!}
                        @endif
                    </dd>
                </div>
                @if ($confirmation->client_id && $confirmation->client)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Client') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            <a href="{{ route('clients.show', $confirmation->client) }}" class="text-bucha-primary hover:underline">{{ $confirmation->client->display_name }}</a>
                        </dd>
                    </div>
                @endif
                @if ($confirmation->contract_id && $confirmation->contract)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Customer contract') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            <a href="{{ route('contracts.show', $confirmation->contract) }}" class="text-bucha-primary hover:underline">{{ $confirmation->contract->contract_number }} — {{ $confirmation->contract->title }}</a>
                        </dd>
                    </div>
                @endif
                @if ($confirmation->fulfillingDemand)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Fulfills demand') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            <a href="{{ route('demands.show', $confirmation->fulfillingDemand) }}" class="text-bucha-primary hover:underline">{{ $confirmation->fulfillingDemand->demand_number }} — {{ $confirmation->fulfillingDemand->title }}</a>
                        </dd>
                    </div>
                @endif
                @if ($confirmation->receiver_address)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs text-slate-500">{{ __('Receiver address') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $confirmation->receiver_address }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Received quantity') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $confirmation->received_quantity }} {{ $confirmation->received_unit ?? 'units' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Received date') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $confirmation->received_date->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Receiver name') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $confirmation->receiver_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Confirmation status') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($confirmation->confirmation_status) }}</dd>
                </div>
            </dl>
        </section>
        @include('delivery-confirmations.partials.export-documents-section', ['confirmation' => $confirmation])
    </div>
</x-app-layout>
