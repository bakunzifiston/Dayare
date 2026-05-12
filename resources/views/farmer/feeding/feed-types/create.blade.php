<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Add feed type') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.feeding.partials.nav')<form method="post" action="{{ route('farmer.feeding.feed-types.store') }}" class="space-y-6">@csrf @include('farmer.feeding.feed-types.partials.form', ['businesses' => $businesses])<x-primary-button>{{ __('Save feed type') }}</x-primary-button></form></div>
</x-app-layout>
