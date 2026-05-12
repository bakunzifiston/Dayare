<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Receive feed stock') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.feeding.partials.nav')<form method="post" action="{{ route('farmer.feeding.inventory.store') }}" class="space-y-6">@csrf @include('farmer.feeding.inventory.partials.form', compact('feedTypes', 'suppliers'))<x-primary-button>{{ __('Save inventory batch') }}</x-primary-button></form></div>
</x-app-layout>
