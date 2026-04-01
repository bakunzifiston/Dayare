<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Order Confirmed') }} - {{ config('app.name', 'BuchaPro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas text-slate-900">
<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="rounded-[18px] border border-emerald-200 bg-white p-8 shadow-bucha text-center">
        <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl">✓</div>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">{{ __('Order Confirmed') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('Thank you for your purchase. Your order has been received.') }}</p>
        <p class="mt-3 text-sm text-slate-700">{{ __('Order number:') }} <span class="font-semibold">{{ $order['order_number'] }}</span></p>
        <p class="mt-1 text-sm text-slate-700">{{ __('Total:') }} <span class="font-semibold text-bucha-primary">RWF {{ number_format($order['total']) }}</span></p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('shop.index') }}" class="inline-flex items-center px-5 py-3 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-sm font-semibold">{{ __('Continue Shopping') }}</a>
            <a href="{{ route('home') }}" class="inline-flex items-center px-5 py-3 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold">{{ __('Back Home') }}</a>
        </div>
    </div>
</main>
@include('layouts.footer')
</body>
</html>
