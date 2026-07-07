@props([
    'facility',
    'monthValue',
    'closure' => [],
    'pdfRoute' => 'rica.monthly-reports.pdf',
])

@php
    use App\Models\RicaMonthlyInspectionReport;

    $isSubmitted = ($closure['status'] ?? RicaMonthlyInspectionReport::STATUS_DRAFT) === RicaMonthlyInspectionReport::STATUS_SUBMITTED;
@endphp

<div class="rica-toolbar">
    <div class="flex flex-wrap items-center gap-2">
        @if ($isSubmitted)
            <span class="rica-status rica-status--submitted">{{ __('Submitted to RICA') }}</span>
            @if (! empty($closure['submitted_at']))
                <span class="text-xs text-slate-500">{{ $closure['submitted_at']->format('d M Y H:i') }}</span>
            @endif
        @elseif (! empty($closure['challenges']) || ! empty($closure['recommendations']))
            <span class="rica-status rica-status--draft">{{ __('Draft saved') }}</span>
        @else
            <span class="rica-status rica-status--pending">{{ __('Sections 1–6 auto-generated') }}</span>
        @endif
    </div>
    <div class="rica-toolbar__actions">
        <a href="{{ route($pdfRoute, ['facility' => $facility, 'month' => $monthValue]) }}"
           class="rica-btn rica-btn--primary">
            {{ __('Download PDF') }}
        </a>
        <button type="button" onclick="window.print()" class="rica-btn rica-btn--secondary">
            {{ __('Print') }}
        </button>
    </div>
</div>
