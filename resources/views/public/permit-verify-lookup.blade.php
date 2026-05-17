<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Verify movement permit') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 text-slate-800">
    <main class="mx-auto max-w-lg px-4 py-16">
        <h1 class="text-2xl font-semibold text-slate-900">{{ __('Verify movement permit') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('Enter permit number, verification code, or QR token.') }}</p>
        <form method="GET" action="{{ route('verify.permit.lookup') }}" class="mt-6 space-y-4">
            <input type="text" name="identifier" value="{{ request('identifier') }}" class="block w-full rounded-lg border-gray-300" placeholder="{{ __('e.g. B260210141531XLJK') }}" required />
            <button type="submit" class="w-full rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Verify') }}</button>
        </form>
    </main>
</body>
</html>
