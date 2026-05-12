<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Mortality record') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.health.partials.nav')<section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm"><p class="text-xs uppercase tracking-wide text-slate-500">{{ $record->mortality_code }}</p><h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $record->cause_of_death }}</h3><p class="mt-2 text-slate-600">{{ $record->death_date?->toDateString() }} · {{ $record->animal?->animal_code }}</p></section></div>
</x-app-layout>
