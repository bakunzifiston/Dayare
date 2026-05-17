<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Edit animal certificate') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">
        @include('farmer.animal-certificates.partials.nav')
        <form method="post" action="{{ route('farmer.certificates.animal-certificates.update', $certificate) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('farmer.animal-certificates.partials.form', compact('certificate', 'animals'))
            <x-primary-button>{{ __('Save changes') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
