<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('404') }} — {{ __('Not Found') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-100 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-8 max-w-md w-full text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 text-slate-500 mb-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-800">{{ __('Page not found') }}</h1>
        <p class="mt-2 text-sm text-slate-600">
            {{ __('This page does not exist, or you do not have access to it. Try going back to your dashboard or the list.') }}
        </p>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Dashboard') }}
                </a>
                <a href="{{ route('businesses.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
                    {{ __('My businesses') }}
                </a>
            @else
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Home') }}
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
                    {{ __('Log in') }}
                </a>
            @endauth
        </div>
    </div>
</body>
</html>
