<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Issue animal certificate') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.animal-certificates.partials.nav')<form method="post" action="{{ route('farmer.certificates.animal-certificates.store') }}" class="space-y-6">@csrf @include('farmer.animal-certificates.partials.form', compact('animals', 'selectedAnimalId'))<div class="flex items-center gap-2"><input id="activate" name="activate" type="checkbox" value="1" checked class="rounded border-gray-300"><label for="activate" class="text-sm text-slate-700">{{ __('Activate immediately') }}</label></div><x-primary-button>{{ __('Create certificate') }}</x-primary-button></form></div>
</x-app-layout>
