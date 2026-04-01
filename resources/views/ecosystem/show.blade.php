<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page['title'] }} - {{ config('app.name', 'BuchaPro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas text-slate-900">
    <header class="sticky top-0 z-20 bg-white/95 border-b border-slate-200/80">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <x-sidebar-brand href="{{ route('home') }}" theme="light" />
            <a href="{{ route('home') }}#ecosystem" class="inline-flex items-center px-4 py-2 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold">
                {{ __('Back to Ecosystem') }}
            </a>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden bg-gradient-to-br from-bucha-charcoal via-bucha-sidebar to-bucha-primary">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, #ffffff 2px, transparent 2px); background-size: 24px 24px;"></div>
            <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
                <p class="text-xs uppercase tracking-wider font-semibold text-white/80">{{ __('Ecosystem') }}</p>
                <h1 class="mt-3 text-3xl sm:text-5xl font-bold text-white">{{ $page['title'] }}</h1>
                <p class="mt-4 text-white/90 max-w-2xl text-sm sm:text-base leading-relaxed">{{ $page['subtitle'] }}</p>
            </div>
        </section>

        <section class="py-10 sm:py-14">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-[18px] border border-slate-200/80 bg-white p-6 sm:p-8 shadow-bucha">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900">{{ __('How BuchaPro helps') }}</h2>
                    <ul class="mt-5 space-y-3 text-sm sm:text-base text-slate-700">
                        @foreach ($page['content'] as $line)
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-bucha-primary"></span>
                                <span>{{ $line }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="inline-flex items-center px-5 py-3 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-sm font-semibold tracking-wide transition-colors">
                            {{ __('Become a Partner') }}
                        </a>
                        <a href="{{ route('contact-us') }}" class="inline-flex items-center px-5 py-3 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold tracking-wide transition-colors">
                            {{ __('Contact Us') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('layouts.footer')
</body>
</html>
