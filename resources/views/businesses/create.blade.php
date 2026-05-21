<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('businesses.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Businesses') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Register Business') }}
            </h2>
        </div>
    </x-slot>

    @include('businesses.partials.onboarding-wizard')
</x-app-layout>
