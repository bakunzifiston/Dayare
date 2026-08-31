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

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                @if ($filterColdRoom)
                    <div>
                        <a href="{{ route('warehouse-storages.index', request()->except('cold_room_id')) }}" class="text-sm text-bucha-primary hover:text-bucha-burgundy">
                            {{ __('Clear room filter') }}
                        </a>
                    </div>
                @endif

                <form method="get" action="{{ route('warehouse-storages.index') }}" class="flex flex-wrap items-end gap-3">
                    @if (request()->filled('cold_room_id'))
                        <input type="hidden" name="cold_room_id" value="{{ request('cold_room_id') }}">
                    @endif
                    @if (request()->filled('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    <div class="min-w-[16rem] flex-1">
                        <label for="storage_search" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                        <input id="storage_search" type="search" name="q" value="{{ request('q') }}"
                               placeholder="{{ __('Ear tag or batch code…') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-bucha-burgundy">
                        {{ __('Search') }}
                    </button>
                    @if (request()->filled('q'))
                        <a href="{{ route('warehouse-storages.index', request()->except('q')) }}" class="text-sm text-bucha-primary hover:text-bucha-burgundy">{{ __('Clear') }}</a>
                    @endif
                </form>

                @if (session('status'))
                    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif

                <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Storage summary') }}">
                    <x-kpi-card stat compact color="slate" :title="__('Total storages')" :value="number_format($kpis['total'])" glyph="box" />
                    <x-kpi-card stat compact color="amber" :title="__('In storage')" :value="number_format($kpis['in_storage'])" glyph="clock" />
                    <x-kpi-card stat compact color="bucha-success" :title="__('Released')" :value="number_format($kpis['released'])" glyph="check" />
                </section>

                @if ($storages->isEmpty())
                    <div class="profile-empty">
                        @if (request()->filled('q'))
                            <p class="mb-4">{{ __('No storage records match your search.') }}</p>
                            <a href="{{ route('warehouse-storages.index', request()->except('q', 'page')) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
                                {{ __('Clear search') }}
                            </a>
                        @else
                            <p class="mb-4">{{ __('No cold room storage records yet.') }}</p>
                            <p class="text-sm text-slate-500 mb-4">{{ __('Record storage of animal meat approved at post-mortem.') }}</p>
                            <a href="{{ route('warehouse-storages.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                                {{ __('Record first storage') }}
                            </a>
                        @endif
                    </div>
                @else
                    @include('warehouse-storages.partials.storage-cards', [
                        'storages' => $storages,
                        'showPagination' => true,
                    ])
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
