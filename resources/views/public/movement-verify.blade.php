<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Movement permit verification') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800">
    <main class="mx-auto max-w-3xl px-4 py-10">
        <header class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Public verification') }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ __('Live animal movement permit') }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ __('Verified at') }}: {{ $verifiedAt->toDateTimeString() }}</p>
        </header>
        <section class="mt-6 rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Permit summary') }}</h2>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
                <div><dt class="text-slate-500">{{ __('Permit number') }}</dt><dd class="font-medium">{{ $permit->permit_number }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Permit status') }}</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $permit->permit_status) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Origin') }}</dt><dd class="font-medium">{{ $permit->origin_location ?: $permit->sourceFarm?->name ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Destination') }}</dt><dd class="font-medium">{{ $permit->destination_location ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Veterinary clearance') }}</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $permit->veterinary_status) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Animal count') }}</dt><dd class="font-medium">{{ $permit->animals->count() }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Departure date') }}</dt><dd class="font-medium">{{ $permit->departure_date?->toDateString() ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Arrival status') }}</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $permit->movement_status) }}</dd></div>
            </dl>
        </section>
        <section class="mt-6 rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Animals authorized for movement') }}</h2>
            <ul class="mt-3 space-y-2">
                @foreach ($permit->animals as $line)
                    <li>{{ $line->animal?->animal_code ?: $line->animal_identifier ?: __('Animal line') }} · {{ ucwords(str_replace('_', ' ', $line->movement_condition)) }}</li>
                @endforeach
            </ul>
        </section>
        <p class="mt-6 text-xs text-slate-500">{{ __('This page shows limited permit information for compliance verification. Sensitive farm and operator records are not displayed.') }}</p>
    </main>
</body>
</html>
