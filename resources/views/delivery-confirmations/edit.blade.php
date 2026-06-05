<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit delivery confirmation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('delivery-confirmations.update', $confirmation) }}" class="space-y-6">
                    @csrf
                    @method('put')
                    @include('delivery-confirmations.partials.form-fields', [
                        'confirmation' => $confirmation,
                        'trips' => $trips,
                        'facilities' => $facilities,
                        'clients' => $clients,
                        'receivedUnits' => $receivedUnits,
                        'contractsUrl' => $contractsUrl,
                    ])
                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update confirmation') }}</x-primary-button>
                        <a href="{{ route('delivery-confirmations.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
