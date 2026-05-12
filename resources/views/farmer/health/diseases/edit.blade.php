<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Edit disease record') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.health.partials.nav')<form method="post" action="{{ route('farmer.health.diseases.update', $record) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('farmer.health.diseases.partials.form', ['animals' => collect([$record->animal]), 'record' => $record])<div class="mt-6 flex gap-3"><x-primary-button>{{ __('Update record') }}</x-primary-button></div></form></div>
</x-app-layout>
