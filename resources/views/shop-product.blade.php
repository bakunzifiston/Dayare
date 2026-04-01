<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product['name'] }} - {{ config('app.name', 'BuchaPro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas text-slate-900">
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('shop.index') }}" class="inline-flex items-center px-4 py-2 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold">{{ __('Back to Shop') }}</a>
            <a href="{{ route('shop.cart') }}" class="inline-flex items-center px-4 py-2 rounded-bucha bg-bucha-primary text-white text-sm font-semibold">
                {{ __('Cart') }}
                <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-white text-bucha-primary text-xs px-1.5">{{ $cartCount }}</span>
            </a>
        </div>

        <div class="mt-6 rounded-[18px] border border-slate-200/80 bg-white shadow-bucha overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <div class="bg-slate-100">
                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="w-full h-full object-cover min-h-[320px]">
                </div>
                <div class="p-6 sm:p-8">
                    <span class="text-xs rounded-full bg-bucha-primary/10 text-bucha-primary px-2 py-1 font-semibold">{{ __($product['badge']) }}</span>
                    <h1 class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900">{{ $product['name'] }}</h1>
                    <p class="mt-2 text-sm text-slate-500">{{ __('Category') }}: {{ __($product['category']) }}</p>
                    <p class="mt-4 text-2xl font-bold text-bucha-primary">RWF {{ number_format($product['price']) }} <span class="text-xs text-slate-500 font-medium">/ {{ $product['unit'] }}</span></p>
                    <p class="mt-4 text-sm text-slate-600">
                        {{ __('This is a premium quality product from the BuchaPro traceable supply chain. Freshly handled, quality checked, and ready for delivery.') }}
                    </p>

                    <div class="mt-6 flex items-center gap-3">
                        <form action="{{ route('shop.cart.add') }}" method="post">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                            <button type="submit" class="inline-flex items-center px-5 py-3 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-sm font-semibold">
                                {{ __('Add to Cart') }}
                            </button>
                        </form>
                        <a href="{{ route('shop.checkout') }}" class="inline-flex items-center px-5 py-3 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold">
                            {{ __('Buy Now') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 rounded-[18px] border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h2 class="text-lg font-bold text-slate-900">{{ __('Full Product Information') }}</h2>
                <p class="mt-3 text-sm text-slate-600 leading-relaxed">
                    {{ __('Our products are sourced from verified facilities and handled under monitored cold-chain conditions. Every batch is linked to traceability records and quality controls to maintain freshness, safety, and consistency.') }}
                </p>

                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-bucha bg-slate-50 border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">{{ __('Origin') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Rwanda certified source') }}</p>
                    </div>
                    <div class="rounded-bucha bg-slate-50 border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">{{ __('Quality grade') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('A / A+ compliant') }}</p>
                    </div>
                    <div class="rounded-bucha bg-slate-50 border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">{{ __('Storage') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('0°C to 4°C chilled') }}</p>
                    </div>
                    <div class="rounded-bucha bg-slate-50 border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">{{ __('Estimated delivery') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Same day / next day') }}</p>
                    </div>
                </div>
            </section>

            <aside class="rounded-[18px] border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h2 class="text-lg font-bold text-slate-900">{{ __('Product Reviews') }}</h2>
                <div class="mt-3 flex items-center gap-2">
                    <p class="text-xl font-bold text-bucha-primary">4.8</p>
                    <p class="text-sm text-slate-600">{{ __('out of 5') }}</p>
                </div>
                <p class="text-xs text-slate-500">{{ __('Based on 126 reviews') }}</p>

                <div class="mt-4 space-y-4">
                    <article class="border-t border-slate-200 pt-4">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Alice M.') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Verified buyer') }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ __('Very fresh and well packaged. Delivery was on time.') }}</p>
                    </article>
                    <article class="border-t border-slate-200 pt-4">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Eric K.') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Verified buyer') }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ __('Good quality meat and clear traceability information.') }}</p>
                    </article>
                    <article class="border-t border-slate-200 pt-4">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Jeanne U.') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Verified buyer') }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ __('Excellent quality. I will order again.') }}</p>
                    </article>
                </div>
            </aside>
        </div>
    </main>
    @include('layouts.footer')
</body>
</html>
