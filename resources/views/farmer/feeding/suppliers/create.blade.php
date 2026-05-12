<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Add supplier') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.feeding.partials.nav')<form method="post" action="{{ route('farmer.feeding.suppliers.store') }}">@csrf @include('farmer.feeding.suppliers.partials.form', compact('businesses', 'feedTypes'))<x-primary-button>{{ __('Save supplier') }}</x-primary-button></form></div>
</x-app-layout>
