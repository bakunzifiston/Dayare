<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Warehouse (cold storage)') }}
            </h2>
            <a href="{{ route('warehouse-storages.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
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
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            @if ($storages->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No warehouse storage records yet.') }}</p>
                    <p class="text-sm mb-4">{{ __('Record storage of certified meat batches. Certificate must be active.') }}</p>
                    <a href="{{ route('warehouse-storages.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Record first storage') }}</a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($storages as $s)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('warehouse-storages.show', $s) }}" class="font-medium text-slate-900 hover:underline">
                                        {{ $s->batch->batch_code ?? '' }} — {{ $s->warehouseFacility->facility_name ?? '' }}
                                    </a>
                                    <p class="text-sm text-slate-500">
                                        {{ $s->entry_date->format('d M Y') }} · {{ $s->storage_location ?? '—' }} · {{ $s->quantity_stored }} {{ __('stored') }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ __('Certificate') }} {{ $s->certificate->certificate_number ?: '#' . $s->certificate_id }} · {{ ucfirst(str_replace('_', ' ', $s->status)) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('warehouse-storages.show', $s) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('warehouse-storages.edit', $s) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">{{ $storages->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
