<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.livestock.index', $farm) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Livestock groups') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ $livestock->livestock_name }}</h2>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="grid gap-3 sm:grid-cols-4">
            <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">{{ __('Code') }}</p><p class="mt-1 font-semibold text-slate-900">{{ $livestock->livestock_code }}</p></div>
            <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">{{ __('Total count') }}</p><p class="mt-1 font-semibold text-slate-900">{{ number_format((int) $livestock->total_count) }}</p></div>
            <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">{{ __('Animals recorded') }}</p><p class="mt-1 font-semibold text-slate-900">{{ number_format((int) $livestock->animals_count) }}</p></div>
            <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">{{ __('Health status') }}</p><p class="mt-1 font-semibold capitalize text-slate-900">{{ str_replace('_', ' ', $livestock->health_status) }}</p></div>
        </div>

        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
            <dl class="grid gap-3 sm:grid-cols-2">
                <div><dt class="text-slate-500">{{ __('Livestock type') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $livestock->livestock_type }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Production purpose') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $livestock->production_purpose ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Lifecycle status') }}</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ str_replace('_', ' ', $livestock->lifecycle_status) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ $livestock->status }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Male / female / young') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $livestock->male_count }} / {{ $livestock->female_count }} / {{ $livestock->young_count }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Housing location') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $livestock->housing_location ?: '—' }}</dd></div>
            </dl>
        </section>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('farmer.farms.livestock.animals.index', [$farm, $livestock]) }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Manage animals') }}</a>
            <a href="{{ route('farmer.farms.livestock.edit', [$farm, $livestock]) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Edit group') }}</a>
        </div>

        <form method="post" action="{{ route('farmer.farms.livestock.destroy', [$farm, $livestock]) }}" onsubmit="return confirm('{{ __('Delete this livestock group?') }}');">
            @csrf
            @method('delete')
            <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">{{ __('Delete livestock group') }}</button>
        </form>
    </div>
</x-app-layout>
