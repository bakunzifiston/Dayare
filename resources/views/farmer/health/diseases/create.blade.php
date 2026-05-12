<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Add disease record') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.health.partials.nav')<form method="post" action="{{ route('farmer.health.diseases.store') }}" enctype="multipart/form-data">@csrf @include('farmer.health.diseases.partials.form', ['animals' => $animals, 'selectedAnimalId' => $selectedAnimalId])<div class="mt-6 flex gap-3"><x-primary-button>{{ __('Save record') }}</x-primary-button></div></form></div>
</x-app-layout>
