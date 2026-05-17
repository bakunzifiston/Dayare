<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('New permit request') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">
        @include('farmer.movement.partials.nav')
        <form method="POST" action="{{ route('farmer.movement.requests.store') }}" class="space-y-6">
            @csrf
            @include('farmer.movement.requests.partials.form', compact('farms', 'animals'))
            <div class="flex flex-wrap gap-3">
                <x-primary-button name="submit" value="0">{{ __('Save draft') }}</x-primary-button>
                <button type="submit" name="submit" value="1" class="rounded-bucha bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Save & submit') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
