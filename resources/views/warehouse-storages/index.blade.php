<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('Storage records') }}
                    @if ($filterColdRoom)
                        <span class="text-base font-normal text-slate-500">— {{ $filterColdRoom->name }}</span>
                    @endif
                </h2>
            </div>
            <a href="{{ route('warehouse-storages.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Record storage') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total storages') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('In storage') }}" :value="$kpis['in_storage']" color="green" />
                <x-kpi-card inline title="{{ __('Released') }}" :value="$kpis['released']" color="slate" />
            </div>

            @if ($filterColdRoom)
                <div class="mb-4">
                    <a href="{{ route('warehouse-storages.index') }}" class="text-sm text-bucha-primary hover:text-bucha-burgundy">
                        {{ __('Clear room filter') }}
                    </a>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($storages->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No cold room storage records yet.') }}</p>
                    <p class="text-sm mb-4">{{ __('Record storage of animal meat approved at post-mortem.') }}</p>
                    <a href="{{ route('warehouse-storages.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Record first storage') }}</a>
                </div>
            @else
                @include('warehouse-storages.partials.storage-table', [
                    'storages' => $storages,
                    'showPagination' => true,
                ])
            @endif
        </div>
    </div>
</x-app-layout>
