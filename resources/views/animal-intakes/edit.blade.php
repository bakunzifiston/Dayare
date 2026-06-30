<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('animal-intakes.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Animal intake') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">{{ __('Edit animal intake') }}</h2>
            </div>
            <a href="{{ route('animal-intakes.show', $intake) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">{{ __('Back to intake') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        @include('animal-intakes.partials.form-wizard', [
            'mode' => 'edit',
            'intake' => $intake,
            'formAction' => route('animal-intakes.update', $intake),
            'formMethod' => 'PATCH',
            'facilities' => $facilities,
            'clients' => $clients,
            'clientsForIntake' => $clientsForIntake,
        ])
    </div>
</x-app-layout>
