@php
    use App\Models\RicaMonthlyInspectionReport;

    $meta = $report['meta'];
    $inspector = $report['inspector'];
    $sh = $report['slaughterhouse'];
    $fmtDate = fn ($date) => $date ? $date->format('d/m/Y') : '—';
    $monthValue = sprintf('%04d-%02d', $year, $month);
    $isSubmitted = ($report['closure']['status'] ?? RicaMonthlyInspectionReport::STATUS_DRAFT) === RicaMonthlyInspectionReport::STATUS_SUBMITTED;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <a href="{{ route('rica.monthly-reports.index', ['month' => $monthValue]) }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy shrink-0">{{ __('← Monthly reports') }}</a>
            <span class="hidden sm:inline text-slate-300" aria-hidden="true">·</span>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight shrink-0">
                {{ $facility->facility_name }}
            </h2>
            <span class="text-sm text-bucha-burgundy font-medium shrink-0">{{ $meta['period_label'] }}</span>
            <form method="GET" action="{{ route('rica.monthly-reports.show', $facility) }}" class="flex flex-wrap items-center gap-2 print:hidden sm:ml-auto">
                <label for="report_month" class="text-xs font-medium text-slate-500 shrink-0">{{ __('Reporting month') }}</label>
                <input type="month" id="report_month" name="month" value="{{ $monthValue }}"
                       class="text-sm rounded-md border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                <x-primary-button>{{ __('Apply') }}</x-primary-button>
            </form>
        </div>
    </x-slot>

    <div class="rica-report-page">
        @include('superadmin.rica.monthly-reports.partials.report-toolbar', [
            'facility' => $facility,
            'monthValue' => $monthValue,
            'closure' => $report['closure'],
        ])

        <article class="rica-report">
            @include('superadmin.rica.monthly-reports.partials.report-cover', ['meta' => $meta])

            <div class="rica-report__body">
                @include('partials.monthly-inspection-report.sections')

                @if ($isSubmitted)
                    @include('partials.monthly-inspection-report.closure-readonly', ['report' => $report])
                @else
                    @include('partials.monthly-inspection-report.closure-pending')
                @endif
            </div>
        </article>
    </div>
</x-app-layout>
