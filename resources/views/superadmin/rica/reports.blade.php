<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('rica.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← RICA') }}</a>
                <div class="mt-1 flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200" aria-hidden="true">
                        <span class="[&>svg]:h-4 [&>svg]:w-4">
                            @include('layouts.partials.sidebar-icon', ['icon' => 'chart'])
                        </span>
                    </span>
                    <div>
                        <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('RICA reports') }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Slaughter and inspection summary across all registered operators.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $sortLink = function (string $column) {
            $direction = request('sort') === $column && request('direction', 'asc') === 'asc' ? 'desc' : 'asc';
            return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction, 'page' => 1]);
        };
        $sortIndicator = function (string $column) {
            if (request('sort', 'facility_name') !== $column) {
                return '';
            }
            return request('direction', 'asc') === 'asc' ? ' ↑' : ' ↓';
        };
    @endphp

    <div class="max-w-7xl mx-auto space-y-6">
        <x-super-admin.tenant-environment-filter
            :action="route('rica.reports')"
            :current="$tenantEnvironmentFilter ?? null"
        />

        <section class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('rica.reports') }}" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-500" for="date_from">{{ __('Date from') }}</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $dateFrom->toDateString() }}"
                           class="mt-1 block text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                </div>
                <div>
                    <label class="text-xs text-gray-500" for="date_to">{{ __('Date to') }}</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $dateTo->toDateString() }}"
                           class="mt-1 block text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                </div>
                <div>
                    <label class="text-xs text-gray-500" for="business_id">{{ __('Operator') }}</label>
                    <select id="business_id" name="business_id" class="mt-1 block text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                        <option value="">{{ __('All operators') }}</option>
                        @foreach ($businesses as $business)
                            <option value="{{ $business->id }}" @selected((string) request('business_id') === (string) $business->id)>{{ $business->business_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500" for="search">{{ __('Facility search') }}</label>
                    <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Slaughterhouse name…') }}"
                           class="mt-1 block text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                </div>
                <div>
                    <span class="text-xs text-gray-500 block">{{ __('Date grouping') }}</span>
                    <div class="mt-1 flex rounded-md border border-gray-300 overflow-hidden text-xs font-semibold">
                        <label class="inline-flex items-center px-3 py-2 cursor-pointer {{ $dateBasis === 'slaughter' ? 'bg-bucha-primary text-white' : 'bg-white text-slate-700' }}">
                            <input type="radio" name="date_basis" value="slaughter" class="sr-only" @checked($dateBasis === 'slaughter')>
                            {{ __('Slaughter date') }}
                        </label>
                        <label class="inline-flex items-center px-3 py-2 cursor-pointer border-l border-gray-300 {{ $dateBasis === 'record' ? 'bg-bucha-primary text-white' : 'bg-white text-slate-700' }}">
                            <input type="radio" name="date_basis" value="record" class="sr-only" @checked($dateBasis === 'record')>
                            {{ __('Record date') }}
                        </label>
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-2">
                    <x-primary-button>{{ __('Apply') }}</x-primary-button>
                    <a href="{{ route('rica.reports') }}" class="inline-flex items-center px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ __('Clear') }}</a>
                    <a href="{{ route('rica.reports.export', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'clipboard'])
                        {{ __('Export CSV') }}
                    </a>
                </div>
            </form>
        </section>

        <p class="text-xs text-gray-500">
            {{ __('Showing: :from – :to', ['from' => $dateFrom->format('d M Y'), 'to' => $dateTo->format('d M Y')]) }}
            · {{ $dateBasis === 'slaughter' ? __('Grouped by slaughter date') : __('Grouped by record date') }}
        </p>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-kpi-card stat glyph="intake" color="blue" :title="__('Total animals slaughtered')" :value="$totals['animals']" />
            <x-kpi-card stat glyph="weight" color="slate" :title="__('Total meat yield')" :value="number_format($totals['total_meat_kg'], 2).' kg'" />
            <x-kpi-card stat glyph="alert" :color="$totals['condemned'] > 0 ? 'bucha' : 'slate'" :title="__('Total condemned')" :value="$totals['condemned']" />
            <x-kpi-card stat glyph="certificate" color="green" :title="__('Certificates issued')" :value="$totals['certificates']" />
        </div>

        <section class="rounded-xl border border-slate-200/80 bg-white overflow-hidden shadow-sm">
            <div class="overflow-x-auto max-h-[70vh]">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-20 bg-slate-50/95 backdrop-blur border-b border-slate-200">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-3 py-3"><a href="{{ $sortLink('facility_name') }}" class="hover:text-bucha-primary">{{ __('Facility') }}{{ $sortIndicator('facility_name') }}</a></th>
                            <th class="px-3 py-3"><a href="{{ $sortLink('operator') }}" class="hover:text-bucha-primary">{{ __('Operator') }}{{ $sortIndicator('operator') }}</a></th>
                            <th class="px-3 py-3 text-right"><a href="{{ $sortLink('animals') }}" class="hover:text-bucha-primary">{{ __('Animals') }}{{ $sortIndicator('animals') }}</a></th>
                            <th class="px-3 py-3 text-right"><a href="{{ $sortLink('total_meat_kg') }}" class="hover:text-bucha-primary">{{ __('Meat (kg)') }}{{ $sortIndicator('total_meat_kg') }}</a></th>
                            <th class="px-3 py-3 text-right"><a href="{{ $sortLink('condemned') }}" class="hover:text-bucha-primary">{{ __('Condemned') }}{{ $sortIndicator('condemned') }}</a></th>
                            <th class="px-3 py-3 text-right"><a href="{{ $sortLink('certificates') }}" class="hover:text-bucha-primary">{{ __('Certificates') }}{{ $sortIndicator('certificates') }}</a></th>
                            <th class="px-3 py-3 text-right"><a href="{{ $sortLink('awaiting_certificate') }}" class="hover:text-bucha-primary">{{ __('Released, no cert.') }}{{ $sortIndicator('awaiting_certificate') }}</a></th>
                            <th class="px-3 py-3 text-right"><a href="{{ $sortLink('avg_cold_room_days') }}" class="hover:text-bucha-primary">{{ __('Avg cold room days') }}{{ $sortIndicator('avg_cold_room_days') }}</a></th>
                            <th class="px-3 py-3 text-right"><a href="{{ $sortLink('temperature_violations') }}" class="hover:text-bucha-primary">{{ __('Temp violations') }}{{ $sortIndicator('temperature_violations') }}</a></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reportRows as $row)
                            @php
                                $detailUrl = route('rica.slaughterhouses.show', [
                                    'facility' => $row['facility']->id,
                                    'date_from' => $dateFrom->toDateString(),
                                    'date_to' => $dateTo->toDateString(),
                                ]);
                            @endphp
                            <tr class="border-t border-slate-100 hover:bg-slate-50 cursor-pointer" onclick="window.location='{{ $detailUrl }}'">
                                <td class="px-3 py-2.5 font-medium text-slate-900">{{ $row['facility']->facility_name }}</td>
                                <td class="px-3 py-2.5 text-slate-600">{{ $row['facility']->business->business_name ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($row['animals']) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($row['total_meat_kg'], 2) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums {{ $row['condemned'] > 0 ? 'text-red-600 font-medium' : 'text-slate-600' }}">{{ number_format($row['condemned']) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($row['certificates']) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums {{ $row['awaiting_certificate'] > 0 ? 'text-amber-700 font-medium' : 'text-slate-600' }}">{{ number_format($row['awaiting_certificate']) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ $row['avg_cold_room_days'] !== null ? number_format($row['avg_cold_room_days'], 1) : '—' }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums {{ $row['temperature_violations'] > 0 ? 'text-red-600 font-medium' : 'text-slate-600' }}">{{ number_format($row['temperature_violations']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-10 text-center text-sm text-slate-500">
                                    {{ __('No slaughterhouses match your filters. Try adjusting the date range or search term.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($reportRows->count() > 0)
                        <tfoot class="sticky bottom-0 bg-slate-100 border-t-2 border-slate-300 font-semibold text-sm">
                            <tr>
                                <td class="px-3 py-2.5" colspan="2">{{ __('Totals') }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($totals['animals']) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($totals['total_meat_kg'], 2) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($totals['condemned']) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($totals['certificates']) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($totals['awaiting_certificate']) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ $totals['avg_cold_room_days'] !== null ? number_format($totals['avg_cold_room_days'], 1) : '—' }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($totals['temperature_violations']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if ($reportRows->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">{{ $reportRows->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
