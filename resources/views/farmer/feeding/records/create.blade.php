<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Log feeding') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.feeding.partials.nav')<form method="post" action="{{ route('farmer.feeding.records.store') }}" class="space-y-6">@csrf @include('farmer.feeding.records.partials.form', compact('animals', 'livestock', 'feedTypes', 'inventories'))<x-primary-button>{{ __('Save feeding record') }}</x-primary-button></form></div>
</x-app-layout>
