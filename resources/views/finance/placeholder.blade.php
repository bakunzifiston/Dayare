<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Finance') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1400px] mx-auto px-0 sm:px-0">
            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <h1 class="text-xl font-semibold text-slate-900">{{ $title ?? __('Finance') }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $description ?? __('This module is under implementation.') }}</p>
            </section>
        </div>
    </div>
</x-app-layout>
