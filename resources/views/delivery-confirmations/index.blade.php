<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Delivery confirmations') }}
            </h2>
            <a href="{{ route('delivery-confirmations.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Confirm delivery') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($confirmations->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    <p class="mb-4">{{ __('No delivery confirmations yet.') }}</p>
                    <a href="{{ route('delivery-confirmations.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Confirm first delivery') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($confirmations as $c)
                            <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                                <div>
                                    <a href="{{ route('delivery-confirmations.show', $c) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $c->transportTrip->vehicle_plate_number ?? '' }} — {{ $c->receivingFacility->facility_name ?? '' }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $c->received_date->format('d M Y') }} · {{ $c->receiver_name }} · {{ $c->received_quantity }} {{ __('received') }} · {{ ucfirst($c->confirmation_status) }}
                                    </p>
                                </div>
                                <a href="{{ route('delivery-confirmations.edit', $c) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">
                        {{ $confirmations->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
