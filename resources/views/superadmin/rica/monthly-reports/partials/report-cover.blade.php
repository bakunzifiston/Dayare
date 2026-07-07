@props(['meta'])

@php
    $fmtDate = fn ($date) => $date ? (\Carbon\Carbon::parse($date)->format('d/m/Y')) : '—';
    $effectiveDate = \Carbon\Carbon::parse($meta['effective_date'])->format('jS F Y');
@endphp

<header class="rica-report__cover">
    <div class="rica-report__cover-flag" aria-hidden="true">
        <span class="flag-blue"></span>
        <span class="flag-yellow"></span>
        <span class="flag-green"></span>
    </div>
    <p class="rica-report__cover-republic">{{ __('REPUBLIC OF RWANDA') }}</p>
    <p class="rica-report__cover-republic-rw">{{ __('REPUBULIKA Y\'U RWANDA') }}</p>
    <h1 class="rica-report__cover-title">{{ __('TITLE: PRIVATE MEAT INSPECTION REPORT FORM') }}</h1>
    <p class="rica-report__cover-subtitle">{{ __('IFISHI YA RAPORO Y\'UMUGENZUZI W\'INYAMA WIGENGA') }}</p>
    <p class="rica-report__cover-note">{{ __('The activities done are reported monthly') }} / {{ __('Ibikorwa byakozwe bitangirwa raporo buri kwezi') }}</p>
    <div class="rica-report__cover-meta">
        <span class="rica-report__badge">{{ $meta['form_id'] }} · {{ __('Rev.') }} {{ $meta['revision'] }}</span>
        <span class="rica-report__badge">{{ __('Effective') }}: {{ $effectiveDate }}</span>
        <span class="rica-report__badge rica-report__badge--highlight">{{ __('Report') }}: {{ $meta['report_number'] }}</span>
        <span class="rica-report__badge rica-report__badge--highlight">{{ __('Period') }}: {{ $meta['period_label'] }}</span>
    </div>
    <div class="rica-report__cover-dates">
        <span>
            <strong>{{ __('Reporting Date') }}</strong> / {{ __('Itariki') }}:
            {{ $fmtDate($meta['reporting_date']) }}
        </span>
        <span>
            {{ $meta['period_start']->format('d/m/Y') }} – {{ $meta['period_end']->format('d/m/Y') }}
        </span>
    </div>
</header>
