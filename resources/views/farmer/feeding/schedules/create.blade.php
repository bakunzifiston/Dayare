<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Add feeding schedule') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.feeding.partials.nav')<form method="post" action="{{ route('farmer.feeding.schedules.store') }}">@csrf @include('farmer.feeding.schedules.partials.form', compact('businesses', 'animals', 'livestock', 'feedTypes'))<x-primary-button>{{ __('Save schedule') }}</x-primary-button></form></div>
</x-app-layout>
