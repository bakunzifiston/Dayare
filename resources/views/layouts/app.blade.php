<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50" x-data="{ sidebarOpen: false }">
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
        class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
        aria-hidden="true"
    ></div>

    @include('layouts.sidebar')

    <div class="lg:pl-64 min-h-screen flex flex-col">
        <!-- Top bar -->
        <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-4 border-b border-slate-200 bg-white px-4 shadow-sm sm:gap-6 sm:px-6">
            <button
                @click="sidebarOpen = true"
                type="button"
                class="lg:hidden -ml-2 p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#2F7DD0]"
                aria-label="Open sidebar"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex-1 min-w-0">
                @isset($header)
                    <div class="flex items-center">
                        {{ $header }}
                    </div>
                @endisset
            </div>
        </header>

        <!-- Main content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
