<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Shop') }} - {{ config('app.name', 'BuchaPro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas text-slate-900">
    <header class="sticky top-0 z-20 bg-white/95 border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <x-sidebar-brand href="{{ route('home') }}" theme="light" />
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold">
                    {{ __('Back Home') }}
                </a>
                <a href="{{ route('shop.cart') }}" class="relative inline-flex items-center px-4 py-2 rounded-bucha bg-bucha-primary text-white text-sm font-semibold">
                    {{ __('Cart') }}
                    <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-white text-bucha-primary text-xs px-1.5">{{ $cartCount }}</span>
                </a>
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden bg-gradient-to-br from-bucha-charcoal via-bucha-sidebar to-bucha-primary">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, #ffffff 2px, transparent 2px); background-size: 24px 24px;"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                <p class="text-xs uppercase tracking-wider font-semibold text-white/80">{{ __('BuchaPro Shop') }}</p>
                <h1 class="mt-3 text-3xl sm:text-5xl font-bold text-white">{{ __('Premium Meat Products') }}</h1>
                <p class="mt-4 text-white/90 max-w-2xl text-sm sm:text-base leading-relaxed">
                    {{ __('Demo storefront: browse certified products, add to cart, and visualize your e-commerce experience.') }}
                </p>
            </div>
        </section>

        <section class="py-10 sm:py-14">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @if (session('shop_notice'))
                    <div class="mb-5 rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('shop_notice') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <aside class="lg:col-span-3">
                        <div class="rounded-[18px] border border-slate-200/80 bg-white p-5 shadow-bucha sticky top-24">
                            <h2 class="text-sm font-bold text-slate-900">{{ __('Categories') }}</h2>
                            <div class="mt-4 space-y-2 text-sm">
                                <a href="#" class="flex items-center justify-between rounded-bucha px-3 py-2 bg-bucha-primary/10 text-bucha-primary font-semibold">
                                    <span>{{ __('All Products') }}</span><span>12</span>
                                </a>
                                <a href="#" class="flex items-center justify-between rounded-bucha px-3 py-2 hover:bg-slate-50 text-slate-700">
                                    <span>{{ __('Beef') }}</span><span>4</span>
                                </a>
                                <a href="#" class="flex items-center justify-between rounded-bucha px-3 py-2 hover:bg-slate-50 text-slate-700">
                                    <span>{{ __('Goat') }}</span><span>3</span>
                                </a>
                                <a href="#" class="flex items-center justify-between rounded-bucha px-3 py-2 hover:bg-slate-50 text-slate-700">
                                    <span>{{ __('Poultry') }}</span><span>3</span>
                                </a>
                                <a href="#" class="flex items-center justify-between rounded-bucha px-3 py-2 hover:bg-slate-50 text-slate-700">
                                    <span>{{ __('Fish') }}</span><span>2</span>
                                </a>
                            </div>
                        </div>
                    </aside>

                    <div class="lg:col-span-9 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach ($products as $product)
                        <article class="rounded-[18px] border border-slate-200/80 bg-white shadow-bucha overflow-hidden">
                            <a href="{{ route('shop.product', $product['id']) }}" class="block w-full">
                                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="h-40 w-full object-cover hover:opacity-95 transition-opacity">
                            </a>
                            <div class="p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900">{{ $product['name'] }}</h3>
                                    <span class="text-[10px] rounded-full bg-bucha-primary/10 text-bucha-primary px-2 py-1 font-semibold">{{ __($product['badge']) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ __($product['category']) }}</p>
                                <p class="mt-3 text-lg font-bold text-bucha-primary">RWF {{ number_format($product['price']) }} <span class="text-xs text-slate-500 font-medium">/ {{ $product['unit'] }}</span></p>
                                <div class="mt-4 flex items-center gap-2">
                                    <form action="{{ route('shop.cart.add') }}" method="post" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy px-3 py-2.5 text-white text-sm font-semibold transition-colors">
                                            {{ __('Add to Cart') }}
                                        </button>
                                    </form>
                                    <a href="{{ route('shop.product', $product['id']) }}" class="inline-flex items-center justify-center rounded-bucha border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                        {{ __('View Product') }}
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')
</body>
</html>
