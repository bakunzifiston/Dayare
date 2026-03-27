<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Sign in') }} — {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-left-bg {
            background: linear-gradient(135deg, #3c3c3b 0%, #a11d1e 100%);
            position: relative;
            overflow: hidden;
        }
        .login-left-bg::before,
        .login-left-bg::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }
        .login-left-bg::before {
            width: 280px;
            height: 280px;
            top: -80px;
            right: -60px;
        }
        .login-left-bg::after {
            width: 180px;
            height: 180px;
            bottom: -40px;
            left: -40px;
        }
    </style>
</head>
<body class="font-sans text-gray-900 antialiased bg-bucha-canvas">
    <div class="min-h-screen flex flex-col sm:justify-center items-center p-4 sm:p-6">
        <div class="w-full max-w-4xl rounded-[28px] overflow-hidden shadow-xl flex flex-col sm:flex-row min-h-[520px]">
            <!-- Left panel -->
            <div class="login-left-bg flex-1 flex items-center px-8 py-12 sm:py-16 sm:px-12 order-2 sm:order-1">
                <div class="relative z-10">
                    <x-sidebar-brand href="{{ route('home') }}" size="hero" class="mb-8 sm:mb-10" />
                    <h1 class="text-white text-3xl sm:text-4xl font-bold uppercase tracking-wide">{{ $leftTitle }}</h1>
                    <p class="text-white text-lg sm:text-xl font-normal uppercase tracking-wide mt-2 opacity-95">{{ $leftSubtitle }}</p>
                    <p class="text-white text-sm sm:text-base mt-6 max-w-md opacity-90 leading-relaxed">
                        {{ $leftDescription }}
                    </p>
                </div>
            </div>
            <!-- Right panel -->
            <div class="flex-1 flex flex-col justify-center bg-white px-8 py-10 sm:py-12 sm:px-12 order-1 sm:order-2 shadow-lg">
                <x-sidebar-brand
                    href="{{ route('home') }}"
                    theme="light"
                    class="mb-8 sm:hidden"
                />
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
