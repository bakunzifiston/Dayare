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

    <div class="max-w-7xl mx-auto space-y-6">

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('rica.reports') }}" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="text-xs text-gray-500" for="date_from">{{ __('Date from') }}</label>
                        <input type="date" id="date_from" name="date_from"
                               value="{{ $dateFrom->toDateString() }}"
                               class="mt-1 block text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500" for="date_to">{{ __('Date to') }}</label>
                        <input type="date" id="date_to" name="date_to"
                               value="{{ $dateTo->toDateString() }}"
                               class="mt-1 block text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500" for="business_id">{{ __('Operator') }}</label>
                        <select id="business_id" name="business_id" class="mt-1 block text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('All operators') }}</option>
                            @foreach ($businesses as $business)
                                <option value="{{ $business->id }}" @selected((string) request('business_id') === (string) $business->id)>
                                    {{ $business->business_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-wrap items-end gap-2">
                        <x-primary-button>{{ __('Apply') }}</x-primary-button>
                        <a href="{{ route('rica.reports') }}"
                           class="inline-flex items-center px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Clear') }}
                        </a>
                        <a href="{{ route('rica.reports.export', request()->query()) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            <span class="[&>svg]:h-4 [&>svg]:w-4 text-slate-500" aria-hidden="true">
                                @include('layouts.partials.sidebar-icon', ['icon' => 'clipboard'])
                            </span>
                            {{ __('Export CSV') }}
                        </a>
                    </div>
                </form>
            </section>

            <p class="text-xs text-gray-500">
                {{ __('Showing: :from – :to', [
                    'from' => $dateFrom->format('d M Y'),
                    'to' => $dateTo->format('d M Y'),
                ]) }}
            </p>

            @php
                $totalAnimals = $reportRows->sum('animals');
                $totalMeat = $reportRows->sum('total_meat_kg');
                $totalCondemned = $reportRows->sum('condemned');
                $totalCerts = $reportRows->sum('certificates');
            @endphp

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <x-kpi-card stat glyph="intake" color="blue" :title="__('Total animals slaughtered')" :value="$totalAnimals" />
                <x-kpi-card stat glyph="weight" color="slate" :title="__('Total meat yield')" :value="number_format($totalMeat, 2).' kg'" />
                <x-kpi-card stat glyph="alert" :color="$totalCondemned > 0 ? 'bucha' : 'slate'" :title="__('Total condemned')" :value="$totalCondemned" />
                <x-kpi-card stat glyph="certificate" color="green" :title="__('Total certificates issued')" :value="$totalCerts" />
            </div>

            @if ($reportRows->isNotEmpty() && $reportRows->every(fn ($r) => $r['animals'] === 0 && $r['certificates'] === 0))
                <p class="text-sm text-gray-400 text-center py-6">
                    {{ __('No slaughter activity recorded in this period.') }}
                </p>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 border-b border-gray-200 bg-slate-50">
                                <th class="pb-2 px-3 pt-3">{{ __('Slaughterhouse') }}</th>
                                <th class="pb-2 px-3 pt-3">{{ __('Operator') }}</th>
                                <th class="pb-2 px-3 pt-3">{{ __('Animals slaughtered') }}</th>
                                <th class="pb-2 px-3 pt-3">{{ __('Total meat (kg)') }}</th>
                                <th class="pb-2 px-3 pt-3">{{ __('Condemned at PM') }}</th>
                                <th class="pb-2 px-3 pt-3">{{ __('Certificates') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportRows as $row)
                                <tr class="border-t border-gray-100 hover:bg-gray-50">
                                    <td class="py-2 px-3">
                                        <a href="{{ route('rica.slaughterhouses.show', [
                                            'facility' => $row['facility']->id,
                                            'date_from' => $dateFrom->toDateString(),
                                            'date_to' => $dateTo->toDateString(),
                                        ]) }}" class="text-blue-600 hover:underline">
                                            {{ $row['facility']->facility_name }}
                                        </a>
                                    </td>
                                    <td class="py-2 px-3 text-gray-600">
                                        {{ $row['facility']->business->business_name ?? '—' }}
                                    </td>
                                    <td class="py-2 px-3 tabular-nums">{{ number_format($row['animals']) }}</td>
                                    <td class="py-2 px-3 tabular-nums">{{ number_format($row['total_meat_kg'], 2) }} kg</td>
                                    <td class="py-2 px-3 tabular-nums {{ $row['condemned'] > 0 ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                        {{ number_format($row['condemned']) }}
                                    </td>
                                    <td class="py-2 px-3 tabular-nums">{{ number_format($row['certificates']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-400 text-sm">
                                        {{ __('No slaughterhouses found for the selected filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($reportRows->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-gray-300 bg-gray-50 font-medium text-sm">
                                    <td class="py-2 px-3 text-xs text-gray-600" colspan="2">{{ __('Totals') }}</td>
                                    <td class="py-2 px-3 tabular-nums">{{ number_format($totalAnimals) }}</td>
                                    <td class="py-2 px-3 tabular-nums">{{ number_format($totalMeat, 2) }} kg</td>
                                    <td class="py-2 px-3 tabular-nums {{ $totalCondemned > 0 ? 'text-red-600' : '' }}">
                                        {{ number_format($totalCondemned) }}
                                    </td>
                                    <td class="py-2 px-3 tabular-nums">{{ number_format($totalCerts) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </section>
    </div>
</x-app-layout>
