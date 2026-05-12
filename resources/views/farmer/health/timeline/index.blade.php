<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Health timeline') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">
        @include('farmer.health.partials.nav')
        <form method="get" class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
            <x-input-label for="animal_id" :value="__('Animal')" />
            <select id="animal_id" name="animal_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                <option value="">{{ __('Select animal') }}</option>
                @foreach ($animals as $animal)
                    <option value="{{ $animal->id }}" @selected($selectedAnimal && $selectedAnimal->id === $animal->id)>{{ $animal->selectionLabel() }}</option>
                @endforeach
            </select>
        </form>
        @if ($selectedAnimal)
            <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Timeline for :animal', ['animal' => $selectedAnimal->displayIdentifier()]) }}</h3>
                <div class="mt-4">@include('farmer.health.partials.timeline', ['events' => $events])</div>
            </section>
        @endif
    </div>
</x-app-layout>
