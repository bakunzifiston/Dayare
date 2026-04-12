<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.index') }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Farms') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ $farm->name }}</h2>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-2 text-sm">
            <p><span class="text-slate-500">{{ __('Business') }}:</span> {{ $farm->business?->business_name }}</p>
            <p><span class="text-slate-500">{{ __('Status') }}:</span> {{ ucfirst($farm->status) }}</p>
            @if ($farm->animal_types)
                <p><span class="text-slate-500">{{ __('Animal types') }}:</span> {{ collect($farm->animal_types)->map(fn ($t) => ucfirst($t))->join(', ') }}</p>
            @endif
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('farmer.farms.livestock.index', $farm) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Manage livestock') }}</a>
            <a href="{{ route('farmer.farms.health-records.index', $farm) }}" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-bucha text-sm">{{ __('Farm health') }}</a>
            <a href="{{ route('farmer.farms.edit', $farm) }}" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-bucha text-sm">{{ __('Edit farm') }}</a>
        </div>

        <form method="post" action="{{ route('farmer.farms.destroy', $farm) }}" onsubmit="return confirm('{{ __('Delete this farm?') }}');">
            @csrf
            @method('delete')
            <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('Delete farm') }}</button>
        </form>
    </div>
</x-app-layout>
