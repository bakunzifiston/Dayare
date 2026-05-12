<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Edit feed type') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.feeding.partials.nav')<form method="post" action="{{ route('farmer.feeding.feed-types.update', $record) }}">@csrf @method('PUT') @include('farmer.feeding.feed-types.partials.form', ['record' => $record])<x-primary-button>{{ __('Update feed type') }}</x-primary-button></form></div>
</x-app-layout>
