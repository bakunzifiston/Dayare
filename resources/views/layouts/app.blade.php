<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas" x-data="{ sidebarOpen: false }">
    <!-- Mobile overlay -->
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-bucha-sidebar/60 lg:hidden"
        aria-hidden="true"
    ></div>

    @include('layouts.sidebar')

    <div class="lg:pl-64 min-h-screen flex flex-col">
        <!-- Top bar -->
        <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-4 border-b border-slate-200/80 bg-bucha-card px-4 shadow-bucha sm:gap-6 sm:px-6">
            <button
                @click="sidebarOpen = true"
                type="button"
                class="lg:hidden -ml-2 p-2 rounded-bucha text-slate-500 hover:text-bucha-primary hover:bg-bucha-canvas focus:outline-none focus:ring-2 focus:ring-inset focus:ring-bucha-primary/40"
                aria-label="Open sidebar"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex-1 min-w-0 flex items-center gap-4">
                <div class="hidden md:flex flex-1 max-w-md">
                    <label class="sr-only" for="top-search">{{ __('Search') }}</label>
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-bucha-muted pointer-events-none">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input id="top-search" type="search" placeholder="{{ __('Search…') }}" class="w-full rounded-bucha border border-slate-200 bg-bucha-canvas py-2 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-bucha-primary focus:ring-1 focus:ring-bucha-primary" />
                    </div>
                </div>
                @isset($header)
                    <div class="flex items-center min-w-0 flex-1 md:flex-none">
                        {{ $header }}
                    </div>
                @endisset
            </div>
            <div class="flex items-center gap-1 shrink-0 text-slate-500">
                <span class="p-2 rounded-bucha hover:bg-bucha-canvas" title="{{ __('Notifications') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </span>
            </div>
        </header>

        <!-- Main content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
        @include('layouts.footer')
    </div>
    @stack('scripts')
</body>
</html>
