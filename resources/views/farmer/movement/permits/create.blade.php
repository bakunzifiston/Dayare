<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Create movement permit') }}</h2></x-slot>
    <div class="max-w-6xl space-y-6">
        @include('farmer.movement.partials.nav')
        <form method="POST" action="{{ route('farmer.movement.permits.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('farmer.movement.permits.partials.form', compact('farms', 'animals'))
            <div class="flex gap-3">
                <x-primary-button>{{ __('Save permit') }}</x-primary-button>
                <a href="{{ route('farmer.movement.permits.index') }}" class="text-sm text-slate-600 hover:underline">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
