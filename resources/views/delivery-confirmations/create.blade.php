<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Confirm delivery') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-medium">{{ __('New on this form') }}</p>
                <ul class="mt-2 list-disc list-inside space-y-1">
                    <li>{{ __('Unit') }} — {{ __('kg, carcasses, boxes, etc. next to received quantity') }}</li>
                    <li>{{ __('Customer contract') }} — {{ __('choose External / non-registered as receiving facility, then pick a client') }}</li>
                    <li>{{ __('International export') }} — {{ __('after saving, if receiver country is not :country, add vet cert, customs, invoice, and cold chain docs on the confirmation page.', ['country' => config('processor.domestic_country', 'RW')]) }}</li>
                </ul>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('delivery-confirmations.store') }}" class="space-y-6">
                    @csrf
                    @include('delivery-confirmations.partials.form-fields', [
                        'trips' => $trips,
                        'facilities' => $facilities,
                        'clients' => $clients,
                        'receivedUnits' => $receivedUnits,
                        'contractsUrl' => $contractsUrl,
                        'preselectedTripId' => $preselectedTripId ?? null,
                    ])
                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save confirmation') }}</x-primary-button>
                        <a href="{{ route('delivery-confirmations.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
