@php
    $view = $view ?? 'submitted';
    $allPeriods = $allPeriods ?? ($view === 'submitted');
    $periodScoped = $periodScoped ?? ($view === 'facilities');
    $monthValue = sprintf('%04d-%02d', $year, $month);
    $filtersApplied = request()->has('apply');
    $filterParams = array_filter([
        'month' => request('month'),
        'business_id' => request('business_id'),
        'search' => request('search'),
        'facility_id' => request('facility_id'),
        'all_periods' => $allPeriods ? '1' : null,
        'apply' => $filtersApplied ? '1' : null,
    ], fn ($value) => $value !== null && $value !== '');
    $scopedFacility = $scopedFacility ?? null;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            @if ($scopedFacility)
                <a href="{{ route('rica.slaughterhouses.show', $scopedFacility) }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy shrink-0">{{ __('← :facility', ['facility' => $scopedFacility->facility_name]) }}</a>
                <span class="hidden sm:inline text-slate-300" aria-hidden="true">·</span>
            @else
                <a href="{{ route('rica.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy shrink-0">{{ __('← RICA') }}</a>
                <span class="hidden sm:inline text-slate-300" aria-hidden="true">·</span>
            @endif
            <div class="inline-flex items-center rounded-lg bg-gradient-to-r from-bucha-burgundy to-bucha-primary px-3 py-1.5 shrink-0">
                <span class="text-xs font-bold uppercase tracking-wide text-white/90">{{ __('FPU/FRM/018') }}</span>
            </div>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight shrink-0">{{ __('Monthly inspection reports') }}</h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-4">
        <nav class="rica-index-tabs" aria-label="{{ __('Report views') }}">
            <a href="{{ route('rica.monthly-reports.index', array_merge($filterParams, ['view' => 'submitted'])) }}"
               class="rica-index-tabs__link {{ $view === 'submitted' ? 'rica-index-tabs__link--active' : '' }}">
                {{ __('Submitted to RICA') }}
            </a>
            <a href="{{ route('rica.monthly-reports.index', array_merge($filterParams, ['view' => 'facilities'])) }}"
               class="rica-index-tabs__link {{ $view === 'facilities' ? 'rica-index-tabs__link--active' : '' }}">
                {{ __('All facilities') }}
            </a>
        </nav>

        <section class="rica-index-filters" x-data="{ allPeriods: @js($allPeriods) }">
            <form method="GET" action="{{ route('rica.monthly-reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <input type="hidden" name="view" value="{{ $view }}">
                @if ($scopedFacility)
                    <input type="hidden" name="facility_id" value="{{ $scopedFacility->id }}">
                @endif
                @if ($view === 'submitted')
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="text-xs font-medium text-slate-500" for="report_month">{{ __('Reporting month') }}</label>
                        <input type="month" id="report_month" name="month" x-ref="reportMonth"
                               value="{{ $allPeriods ? (request('month') ?: '') : $monthValue }}"
                               :disabled="allPeriods"
                               class="mt-1 block w-full text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary disabled:bg-slate-50 disabled:text-slate-400">
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="all_periods" value="1"
                                   x-model="allPeriods"
                                   @change="if (!allPeriods && !$refs.reportMonth.value) { $refs.reportMonth.value = @js($monthValue); }"
                                   class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary">
                            {{ __('All periods') }}
                        </label>
                    </div>
                @else
                    <div>
                        <label class="text-xs font-medium text-slate-500" for="report_month">{{ __('Month') }}</label>
                        <input type="month" id="report_month" name="month" value="{{ $monthValue }}"
                               class="mt-1 block w-full text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                    </div>
                @endif
                <div>
                    <label class="text-xs font-medium text-slate-500" for="business_id">{{ __('Operator') }}</label>
                    <select id="business_id" name="business_id" class="mt-1 block w-full text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                        <option value="">{{ __('All operators') }}</option>
                        @foreach ($businesses as $business)
                            <option value="{{ $business->id }}" @selected((string) request('business_id') === (string) $business->id)>{{ $business->business_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-1">
                    <label class="text-xs font-medium text-slate-500" for="search">{{ __('Search') }}</label>
                    <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Facility name…') }}"
                           class="mt-1 block w-full text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                </div>
                <div>
                    <x-primary-button type="submit" name="apply" value="1" class="w-full sm:w-auto justify-center">{{ __('Apply') }}</x-primary-button>
                </div>
            </form>
        </section>

        @if ($periodScoped)
            <p class="rica-index-hint">
                {{ __(':count facilities for :period.', ['count' => $facilities->total(), 'period' => $periodStart->format('F Y')]) }}
            </p>

            <section class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="rica-index-table">
                        <thead>
                            <tr>
                                <th>{{ __('Slaughterhouse') }}</th>
                                <th>{{ __('Operator') }}</th>
                                <th>{{ __('District') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($facilities as $facility)
                                @php $submission = $submissionStatuses[$facility->id] ?? null; @endphp
                                <tr>
                                    <td class="font-medium text-slate-900">
                                        {{ $facility->facility_name }}
                                        @if ($facility->facility_type !== \App\Models\Facility::TYPE_SLAUGHTERHOUSE)
                                            <span class="text-xs font-normal text-slate-400">({{ $facility->facility_type }})</span>
                                        @endif
                                    </td>
                                    <td class="text-slate-600">{{ $facility->business->business_name ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $facility->districtDivision?->name ?? $facility->district ?? '—' }}</td>
                                    <td>
                                        @if ($submission?->isSubmitted())
                                            <span class="rica-status rica-status--submitted">{{ __('Submitted to RICA') }}</span>
                                        @elseif ($submission)
                                            <span class="rica-status rica-status--draft">{{ __('Draft') }}</span>
                                        @else
                                            <span class="rica-status rica-status--pending">{{ __('Not started') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('rica.monthly-reports.show', ['facility' => $facility, 'month' => $monthValue]) }}"
                                           class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                                            {{ __('Open report') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                                        {{ __('No facilities match your filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($facilities->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">{{ $facilities->links() }}</div>
                @endif
            </section>
        @else
            <p class="rica-index-hint">
                @if ($scopedFacility)
                    {{ __(':count reports submitted to RICA for :facility across all periods.', ['count' => $submittedReports->total(), 'facility' => $scopedFacility->facility_name]) }}
                @else
                    {{ __(':count reports submitted to RICA across all periods.', ['count' => $submittedReports->total()]) }}
                @endif
            </p>

            <section class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="rica-index-table">
                        <thead>
                            <tr>
                                <th>{{ __('Reporting period') }}</th>
                                <th>{{ __('Slaughterhouse') }}</th>
                                <th>{{ __('Operator') }}</th>
                                <th>{{ __('District') }}</th>
                                <th>{{ __('Submitted on') }}</th>
                                <th>{{ __('Submitted by') }}</th>
                                <th class="text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submittedReports as $submission)
                                @php
                                    $facility = $submission->facility;
                                    $periodLabel = \Carbon\Carbon::create($submission->period_year, $submission->period_month, 1)->format('F Y');
                                    $periodMonth = sprintf('%04d-%02d', $submission->period_year, $submission->period_month);
                                @endphp
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $periodLabel }}</td>
                                    <td class="text-slate-900">{{ $facility?->facility_name ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $facility?->business?->business_name ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $facility?->districtDivision?->name ?? $facility?->district ?? '—' }}</td>
                                    <td class="text-slate-600 whitespace-nowrap">{{ $submission->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $submission->submittedBy?->name ?? '—' }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        @if ($facility)
                                            <a href="{{ route('rica.monthly-reports.show', ['facility' => $facility, 'month' => $periodMonth]) }}"
                                               class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                                                {{ __('View') }}
                                            </a>
                                            <span class="text-slate-300 mx-1" aria-hidden="true">·</span>
                                            <a href="{{ route('rica.monthly-reports.pdf', ['facility' => $facility, 'month' => $periodMonth]) }}"
                                               class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                                                {{ __('PDF') }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-sm text-slate-500">
                                        {{ __('No reports have been submitted to RICA for the selected filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($submittedReports->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">{{ $submittedReports->links() }}</div>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>
