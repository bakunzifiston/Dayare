<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Logistics dashboard') }}</span>
    </x-slot>

    <div class="py-4 lg:py-6 max-w-3xl">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
            {{ __('Welcome, :name', ['name' => $user->name]) }}
        </h1>
        <p class="mt-2 text-sm text-bucha-muted">
            {{ __('Your logistics workspace. Complete your business profile when you are ready to manage verified transport.') }}
        </p>
    </div>
</x-app-layout>
