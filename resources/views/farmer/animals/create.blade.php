<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.livestock.animals.index', [$farm, $livestock]) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Animals') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('New animal') }}</h2>
    </x-slot>

    <div class="max-w-4xl">
        <form method="post" action="{{ route('farmer.farms.livestock.animals.store', [$farm, $livestock]) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('farmer.animals.partials.form')
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save animal') }}</button>
                <a href="{{ route('farmer.farms.livestock.animals.index', [$farm, $livestock]) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
