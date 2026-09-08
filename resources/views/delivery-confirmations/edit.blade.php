<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('delivery-confirmations.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Delivery') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Edit delivery confirmation') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bucha-wizard-panel">
                @if ($errors->any())
                    <div class="mb-6 rounded-bucha border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-medium">{{ __('Please fix the following:') }}</p>
                        <ul class="mt-2 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('delivery-confirmations.update', $confirmation) }}">
                    @csrf
                    @method('put')
                    @include('delivery-confirmations.partials.form-fields', [
                        'confirmation' => $confirmation,
                        'trips' => $trips,
                        'clients' => $clients,
                        'receivedUnits' => $receivedUnits,
                        'contractsUrl' => $contractsUrl,
                        'destinationCountries' => $destinationCountries ?? [],
                        'submitLabel' => __('Update confirmation'),
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
