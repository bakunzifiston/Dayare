<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Animal passport PDF') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800">
    <main class="mx-auto max-w-lg px-4 py-12">
        <header class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Public lookup') }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ __('Download animal passport (PDF)') }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ __('Enter the ear tag, farm animal code, or verification code. You will get a PDF with identity, farm origin, and traceability summary.') }}</p>
        </header>

        <form method="post" action="{{ route('animal.passport.pdf') }}" class="mt-6 rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            @csrf
            <div>
                <x-input-label for="identifier" :value="__('Tag or animal code')" />
                <x-text-input
                    id="identifier"
                    name="identifier"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('identifier')"
                    required
                    autofocus
                    autocomplete="off"
                    placeholder="{{ __('e.g. ear tag or BuchaPro animal code') }}"
                />
                <x-input-error :messages="$errors->get('identifier')" class="mt-2" />
            </div>
            <p class="mt-3 text-xs text-slate-500">{{ __('Tip: you can also bookmark a direct link:') }} <code class="rounded bg-slate-100 px-1 py-0.5 text-[11px]">{{ url('/animal-passport/pdf') }}?identifier=…</code></p>

        <p class="mt-6 text-center text-xs text-slate-500">
            <a href="{{ route('home') }}" class="text-bucha-primary hover:underline">{{ __('← Back to home') }}</a>
        </p>
    </main>
</body>
</html>
