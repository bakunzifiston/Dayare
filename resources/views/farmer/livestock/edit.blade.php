<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.livestock.show', [$farm, $livestock]) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Livestock group') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Edit livestock group') }}</h2>
    </x-slot>

    <div class="max-w-4xl">
        <form method="post" action="{{ route('farmer.farms.livestock.update', [$farm, $livestock]) }}" class="space-y-6">
            @csrf
            @method('put')
            @include('farmer.livestock.partials.form', ['types' => $types, 'livestock' => $livestock])
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Update livestock group') }}</button>
                <a href="{{ route('farmer.farms.livestock.show', [$farm, $livestock]) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Cancel') }}</a>
            </div>
        </form>

        <form method="post" action="{{ route('farmer.farms.livestock.destroy', [$farm, $livestock]) }}" class="mt-6" onsubmit="return confirm('{{ __('Delete this livestock group?') }}');">
            @csrf
            @method('delete')
            <button type="submit" class="inline-flex items-center rounded-bucha border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">{{ __('Delete livestock group') }}</button>
        </form>
    </div>
</x-app-layout>
