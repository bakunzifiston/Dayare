<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Edit treatment') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.health.partials.nav')<form method="post" action="{{ route('farmer.health.treatments.update', $record) }}" enctype="multipart/form-data" class="space-y-6">@csrf @method('PUT') @include('farmer.health.treatments.partials.form', ['animals' => collect([$record->animal]), 'record' => $record])<div class="flex gap-3"><x-primary-button>{{ __('Update treatment') }}</x-primary-button><a href="{{ route('farmer.health.treatments.show', $record) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Cancel') }}</a></div></form></div>
</x-app-layout>
