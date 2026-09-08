<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">{{ __('Edit cold room') }}</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            <div class="bucha-wizard-panel">
                @include('cold-rooms.partials.form-fields', [
                    'room' => $room,
                    'facilities' => $facilities,
                    'standards' => $standards,
                    'submitLabel' => __('Update'),
                ])
            </div>
        </div>
    </div>
</x-app-layout>
