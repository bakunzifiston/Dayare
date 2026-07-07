<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <a href="{{ route('animal-intakes.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy shrink-0">{{ __('← Animal intake') }}</a>
            <span class="hidden sm:inline text-slate-300" aria-hidden="true">·</span>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight shrink-0">{{ __('Record animal intake') }}</h2>
        </div>
    </x-slot>

    <div class="py-12">
        @include('animal-intakes.partials.form-wizard', [
            'mode' => 'create',
            'formAction' => route('animal-intakes.store'),
            'formMethod' => 'POST',
            'facilities' => $facilities,
            'clients' => $clients,
            'clientsForIntake' => $clientsForIntake,
        ])
    </div>
</x-app-layout>
