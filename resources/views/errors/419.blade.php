<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('419') }} — {{ __('Page Expired') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Figtree', sans-serif; }
    </style>
</head>
<body class="font-sans antialiased bg-slate-100 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-8 max-w-md w-full text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-100 text-amber-600 mb-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-800">{{ __('Page Expired') }}</h1>
        <p class="mt-2 text-sm text-slate-600">
            {{ __('Your session expired or the page was open too long. For your security, please refresh the page and try again.') }}
        </p>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ $refreshUrl ?? url('/') }}" class="inline-flex items-center justify-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Refresh page') }}
            </a>
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
                {{ __('Log in') }}
            </a>
        </div>
    </div>
</body>
</html>
