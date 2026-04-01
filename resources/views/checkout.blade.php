<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Checkout') }} - {{ config('app.name', 'BuchaPro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas text-slate-900">
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Checkout') }}</h1>
        <a href="{{ route('shop.cart') }}" class="inline-flex items-center px-4 py-2 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold">{{ __('Back to cart') }}</a>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form action="{{ route('shop.place-order') }}" method="post" class="lg:col-span-2 rounded-[18px] border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
            @csrf
            <h2 class="text-sm font-bold text-slate-900">{{ __('Customer details') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">{{ __('Full name') }}</label>
                    <input name="full_name" value="{{ old('full_name') }}" required class="mt-1 w-full rounded-bucha border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-bucha border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Phone') }}</label>
                    <input name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded-bucha border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">{{ __('Delivery address') }}</label>
                    <textarea name="address" rows="3" required class="mt-1 w-full rounded-bucha border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('address') }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">{{ __('Payment method') }}</label>
                    <select name="payment_method" required class="mt-1 w-full rounded-bucha border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary">
                        <option value="mobile_money">{{ __('Mobile Money') }}</option>
                        <option value="card">{{ __('Card') }}</option>
                        <option value="cash_on_delivery">{{ __('Cash on Delivery') }}</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="inline-flex items-center px-5 py-3 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-sm font-semibold">
                {{ __('Place Order') }}
            </button>
        </form>

        <aside class="rounded-[18px] border border-slate-200/80 bg-white p-5 shadow-bucha h-fit">
            <h2 class="text-sm font-bold text-slate-900">{{ __('Order Summary') }}</h2>
            <div class="mt-3 space-y-2 text-sm">
                @foreach ($items as $item)
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-600">{{ $item['name'] }} x{{ $item['quantity'] }}</span>
                        <span class="font-medium text-slate-900">RWF {{ number_format($item['line_total']) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 pt-4 border-t border-slate-200 space-y-2 text-sm">
                <div class="flex items-center justify-between"><span class="text-slate-600">{{ __('Subtotal') }}</span><span class="font-medium">RWF {{ number_format($subtotal) }}</span></div>
                <div class="flex items-center justify-between"><span class="text-slate-600">{{ __('Shipping') }}</span><span class="font-medium">RWF {{ number_format($shipping) }}</span></div>
                <div class="flex items-center justify-between text-base"><span class="font-bold text-slate-900">{{ __('Total') }}</span><span class="font-bold text-bucha-primary">RWF {{ number_format($total) }}</span></div>
            </div>
        </aside>
    </div>
</main>
@include('layouts.footer')
</body>
</html>
