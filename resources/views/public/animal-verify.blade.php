<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Animal verification') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800">
    <main class="mx-auto max-w-3xl px-4 py-10">
        <header class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Public verification') }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ __('Animal traceability passport') }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ __('Verified at') }}: {{ $verifiedAt->toDateTimeString() }}</p>
        </header>
        <section class="mt-6 rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Animal identity') }}</h2>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
                <div><dt class="text-slate-500">{{ __('Animal code') }}</dt><dd class="font-medium">{{ $animal->animal_code }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Tag number') }}</dt><dd class="font-medium">{{ $animal->tag_number ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Health status') }}</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $animal->health_status) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Lifecycle status') }}</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $animal->lifecycle_status) }}</dd></div>
            </dl>
        </section>
        <section class="mt-6 rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Farm origin') }}</h2>
            <p class="mt-2">{{ $summary['farm']?->name ?: '—' }}</p>
            <p class="text-slate-600">{{ $summary['farm_location'] }}</p>
            <p class="mt-2">{{ __('Current owner') }}: {{ $summary['current_owner'] }}</p>
        </section>
        <section class="mt-6 rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Health & traceability') }}</h2>
            <ul class="mt-3 space-y-2"><li>{{ $summary['health_summary'] }}</li><li>{{ $summary['vaccination_summary'] }}</li><li>{{ $summary['traceability_status'] }}</li></ul>
        </section>
        @if ($certificate)
            <section class="mt-6 rounded-bucha border border-emerald-200 bg-emerald-50 p-6 text-sm">
                <h2 class="font-semibold text-emerald-900">{{ __('Certificate validity') }}</h2>
                <p class="mt-2">{{ $certificate->certificate_number }} · <x-certificate-status-badge :status="$certificate->certificate_status" /></p>
                <p class="mt-2">{{ $certificate->isPubliclyValid() ? __('Certificate is valid.') : __('Certificate is not currently valid.') }}</p>
            </section>
        @endif
    </main>
</body>
</html>
