<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Delivery confirmations') }}
            </h2>
            <a href="{{ route('delivery-confirmations.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                {{ __('Confirm delivery') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                                        {{ $c->received_date->format('d M Y') }} · {{ $c->receiver_name }} · {{ $c->received_quantity }} {{ __('received') }} · {{ ucfirst($c->confirmation_status) }}
                                    </p>
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
