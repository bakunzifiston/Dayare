<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800">{{ __('Edit vaccination') }}</h2>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        @include('farmer.health.partials.nav')
        <form method="post" action="{{ route('farmer.health.vaccinations.update', $record) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            @include('farmer.health.vaccinations.partials.form', ['animals' => collect([$record->animal]), 'record' => $record])
            <div class="flex gap-3">
                <x-primary-button>{{ __('Update vaccination') }}</x-primary-button>
                <a href="{{ route('farmer.health.vaccinations.show', $record) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
