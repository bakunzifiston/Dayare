<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Cart') }} - {{ config('app.name', 'BuchaPro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas text-slate-900">
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Your Cart') }}</h1>
        <a href="{{ route('shop.index') }}" class="inline-flex items-center px-4 py-2 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold">{{ __('Continue shopping') }}</a>
    </div>

    @if (session('shop_notice'))
        <div class="mt-4 rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('shop_notice') }}
        </div>
    @endif

    @if ($items === [])
        <div class="mt-6 rounded-[18px] border border-slate-200/80 bg-white p-8 text-center shadow-bucha">
            <p class="text-slate-600">{{ __('Your cart is empty.') }}</p>
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                @foreach ($items as $item)
                    <article class="rounded-[18px] border border-slate-200/80 bg-white p-4 shadow-bucha">
                        <div class="flex gap-4">
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-20 w-24 rounded-bucha object-cover">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $item['category'] }}</p>
                                <p class="mt-1 text-sm font-bold text-bucha-primary">RWF {{ number_format($item['price']) }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <form action="{{ route('shop.cart.update') }}" method="post" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="100" class="w-16 rounded-bucha border-slate-300 text-sm">
                                    <button type="submit" class="px-3 py-1.5 rounded-bucha border border-slate-200 text-xs font-semibold hover:bg-slate-50">{{ __('Update') }}</button>
                                </form>
                                <form action="{{ route('shop.cart.remove') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">{{ __('Remove') }}</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="rounded-[18px] border border-slate-200/80 bg-white p-5 shadow-bucha h-fit">
                <h2 class="text-sm font-bold text-slate-900">{{ __('Order Summary') }}</h2>
                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="text-slate-600">{{ __('Subtotal') }}</span>
                    <span class="font-semibold text-slate-900">RWF {{ number_format($subtotal) }}</span>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ __('Shipping is calculated at checkout.') }}</p>
                <a href="{{ route('shop.checkout') }}" class="mt-5 inline-flex w-full items-center justify-center rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy px-4 py-2.5 text-white text-sm font-semibold">
                    {{ __('Proceed to Checkout') }}
                </a>
            </div>
        </div>
    @endif
</main>
@include('layouts.footer')
</body>
</html>
