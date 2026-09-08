<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">{{ __('New cold room') }}</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            <div class="bucha-wizard-panel">
                @if ($facilities->isEmpty())
                    <p class="text-sm text-amber-700">{{ __('You need a facility with type “storage” first. Add one under Businesses → Facilities.') }}</p>
                    <a href="{{ route('businesses.hub') }}" class="mt-4 inline-block text-sm font-medium text-bucha-primary hover:underline">{{ __('Go to businesses') }}</a>
                @else
                    @include('cold-rooms.partials.form-fields', [
                        'facilities' => $facilities,
                        'standards' => $standards,
                        'submitLabel' => __('Save'),
                    ])
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
