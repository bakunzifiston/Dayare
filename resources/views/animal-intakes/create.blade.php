<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('animal-intakes.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Animal intake') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">{{ __('Record animal intake') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Register each animal individually — ear tag, species, health status, and price.') }}</p>
        </div>
    </x-slot>

    <div class="py-12">
        @include('animal-intakes.partials.form-wizard', [
            'mode' => 'create',
            'formAction' => route('animal-intakes.store'),
            'formMethod' => 'POST',
            'facilities' => $facilities,
            'suppliers' => $suppliers,
            'clients' => $clients,
            'suppliersForIntake' => $suppliersForIntake,
            'clientsForIntake' => $clientsForIntake,
            'supplierContracts' => $supplierContracts,
        ])
    </div>
</x-app-layout>
