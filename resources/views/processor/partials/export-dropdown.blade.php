@props([
    'exportRoute',
    'traceabilityRoute' => null,
    'query' => [],
])

@php
    $user = auth()->user();
    $canExport = $user && $user->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_EXPORT_RECORDS);
    $canTraceability = $traceabilityRoute && $user && $user->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_EXPORT_TRACEABILITY);
    $params = array_filter($query, fn ($v) => $v !== null && $v !== '');
@endphp

@if ($canExport)
    <div class="relative inline-block text-left" x-data="{ open: false }">
        <button type="button" @click="open = !open" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
            {{ __('Export') }}
        </button>
        <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 z-20 mt-2 w-52 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
            <a href="{{ route($exportRoute, array_merge($params, ['format' => 'csv'])) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ __('CSV') }}</a>
            <a href="{{ route($exportRoute, array_merge($params, ['format' => 'excel'])) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ __('Excel') }}</a>
            <a href="{{ route($exportRoute, array_merge($params, ['format' => 'pdf'])) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ __('PDF report') }}</a>
            <a href="{{ route($exportRoute, array_merge($params, ['format' => 'json'])) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ __('JSON') }}</a>
            @if ($canTraceability && $traceabilityRoute)
                <div class="my-1 border-t border-slate-100"></div>
                <a href="{{ route($traceabilityRoute, $params) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ __('Traceability PDF (full chain)') }}</a>
            @endif
        </div>
    </div>
@endif
