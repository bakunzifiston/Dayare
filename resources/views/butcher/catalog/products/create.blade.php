<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New product') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Link a cut type to auto-track cost from cutting sessions.') }}</p>
            </div>
            <a href="{{ route('butcher.catalog.products.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Back') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('butcher.catalog.products.partials.form', [
                'action' => route('butcher.catalog.products.store'),
                'method' => 'POST',
                'product' => null,
                'cutTypes' => $cutTypes,
                'meatTypes' => $meatTypes,
                'units' => $units,
            ])
        </div>
    </div>
</x-app-layout>
