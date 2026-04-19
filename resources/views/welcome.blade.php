<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas text-slate-900">
    <header class="sticky top-0 z-20 bg-white/95 border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <x-sidebar-brand href="{{ route('home') }}" theme="light" />
            <nav class="hidden lg:flex items-center gap-5 text-sm font-semibold text-slate-700">
                <a href="#mobile-platform" class="hover:text-bucha-primary transition-colors">{{ __('Mobile') }}</a>
                <a href="#what-is-buchapro" class="hover:text-bucha-primary transition-colors">{{ __('About') }}</a>
                <a href="#how-it-works" class="hover:text-bucha-primary transition-colors">{{ __('How it works') }}</a>
                <a href="#ecosystem" class="hover:text-bucha-primary transition-colors">{{ __('Ecosystem') }}</a>
                <a href="#platform-features" class="hover:text-bucha-primary transition-colors">{{ __('Features') }}</a>
                <a href="#products" class="hover:text-bucha-primary transition-colors">{{ __('Products') }}</a>
                <a href="{{ route('contact-us') }}" class="hover:text-bucha-primary transition-colors">{{ __('Contact') }}</a>
            </nav>
            <div class="flex items-center gap-2 sm:gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-xs sm:text-sm font-semibold tracking-wide transition-colors">
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 rounded-bucha border border-slate-200 hover:bg-slate-50 text-xs sm:text-sm font-semibold tracking-wide transition-colors">
                            {{ __('Sign in') }}
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-xs sm:text-sm font-semibold tracking-wide transition-colors">
                                {{ __('Get started') }}
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <main>
        {{-- HERO SECTION --}}
        <section class="relative overflow-hidden bg-gradient-to-br from-bucha-charcoal via-bucha-sidebar to-bucha-primary">
            <img
                src="{{ asset('images/Abattoir-For-Livestock-Meat-3a9cfc1f-683f-49ec-bec4-5aceb28cd5f5.png') }}"
                alt="{{ __('Meat integrity banner') }}"
                class="absolute inset-0 w-full h-full object-cover opacity-40"
            />
            <div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(circle at 20% 20%, #ffffff 2px, transparent 2px); background-size: 24px 24px;"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div>
                        <h1 class="text-white text-3xl sm:text-5xl font-extrabold tracking-tight">
                            {{ __('The Gold Standard for Meat Integrity') }}
                        </h1>
                        <p class="mt-5 text-white/90 text-base sm:text-lg max-w-xl leading-relaxed">
                            {{ __('BuchaPro bridges the gap between farm and table with end-to-end traceability, certified cold-chain logistics, and a digital layer of trust.') }}
                        </p>
                        <p class="mt-4 text-white text-sm sm:text-base font-semibold tracking-wider uppercase">
                            {{ __('Track. Verify. Trust.') }}
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('contact-us') }}" class="inline-flex items-center px-5 py-3 rounded-bucha bg-white text-bucha-primary hover:bg-slate-100 font-semibold text-sm tracking-wide transition-colors">
                                {{ __('Contact Us') }}
                            </a>
                            <a href="{{ route('shop.index') }}" class="inline-flex items-center px-5 py-3 rounded-bucha border border-white/30 bg-white/10 text-white hover:bg-white/20 font-semibold text-sm tracking-wide transition-colors">
                                {{ __('Shop Now') }}
                            </a>
                        </div>
                    </div>

                    <div class="rounded-[20px] bg-white/10 border border-white/20 p-5 sm:p-6 shadow-xl">
                        <div class="relative">
                            <p class="text-white/80 text-xs uppercase tracking-wider mb-3">{{ __('Platform Preview') }}</p>
                            <div class="rounded-bucha bg-white/95 p-4 sm:p-5 border border-slate-200/80">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                        <p class="text-[11px] uppercase text-slate-500 tracking-wider">{{ __('Traceability') }}</p>
                                        <p class="text-xl font-bold text-bucha-primary mt-1">100%</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                        <p class="text-[11px] uppercase text-slate-500 tracking-wider">{{ __('Compliance') }}</p>
                                        <p class="text-xl font-bold text-slate-900 mt-1">A+</p>
                                    </div>
                                    <div class="col-span-2 rounded-lg border border-slate-200 bg-white p-3">
                                        <p class="text-[11px] uppercase text-slate-500 tracking-wider">{{ __('Live chain status') }}</p>
                                        <div class="mt-2 flex items-center justify-between text-xs text-slate-700">
                                            <span>{{ __('Source') }}</span>
                                            <span>{{ __('Process') }}</span>
                                            <span>{{ __('Transport') }}</span>
                                            <span>{{ __('Verify') }}</span>
                                            <span>{{ __('Deliver') }}</span>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-slate-200 overflow-hidden">
                                            <div class="h-full w-4/5 bg-bucha-primary"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECOND SECTION: MOBILE PLATFORM SHOWCASE --}}
        <section id="mobile-platform" class="border-y border-slate-200/80 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-12 items-center">
                    {{-- RIGHT SIDE (phones) on mobile first --}}
                    <div class="order-1 lg:order-2 relative flex justify-center">
                        <div class="relative">
                            <div class="absolute -inset-4 sm:-inset-6 rounded-[28px] bg-gradient-to-br from-bucha-primary/20 to-bucha-charcoal/10 blur-2xl"></div>
                            <img
                                src="{{ asset('images/buchapro-mobile-showcase.png') }}"
                                alt="{{ __('BuchaPro mobile app screens') }}"
                                class="relative w-full max-w-[560px] rounded-[22px] shadow-2xl border border-slate-200/80 rotate-[-2deg]"
                            >
                            <div class="absolute -bottom-3 -right-2 sm:-bottom-4 sm:-right-4 rounded-bucha bg-white border border-slate-200 shadow-bucha px-3 py-2 rotate-[3deg]">
                                <p class="text-[11px] uppercase tracking-wider text-bucha-muted">{{ __('Live Demo') }}</p>
                                <p class="text-xs font-semibold text-slate-700">{{ __('Tracking • Traceability • Alerts') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- LEFT SIDE --}}
                    <div class="order-2 lg:order-1">
                        <p class="text-xs uppercase tracking-wider font-semibold text-bucha-muted">{{ __('Mobile Platform') }}</p>
                        <h2 class="mt-3 text-2xl sm:text-4xl font-bold text-slate-900">{{ __('Transparency in Your Pocket') }}</h2>
                        <p class="mt-4 text-sm sm:text-base text-slate-600 max-w-xl leading-relaxed">
                            {{ __('Track shipments in real time, verify meat origin and batch history, and monitor temperature and compliance from one mobile interface.') }}
                        </p>

                        <div class="mt-7 grid grid-cols-1 sm:grid-cols-[190px_1fr] gap-4 sm:gap-6 items-start">
                            <div class="rounded-bucha border border-slate-200 bg-slate-50 p-3 text-center">
                                <p class="text-[11px] uppercase tracking-wider text-bucha-muted mb-2">{{ __('Scan to Download') }}</p>
                                {{-- QR placeholder block --}}
                                <div class="mx-auto h-[130px] w-[130px] rounded-md bg-white border border-slate-200 p-2 grid grid-cols-9 gap-[2px]">
                                    @for ($i = 0; $i < 81; $i++)
                                        <span class="{{ in_array($i % 9, [0,1,2,6,7,8], true) && in_array(intdiv($i,9), [0,1,2,6,7,8], true) ? 'bg-slate-900' : (in_array(($i + intdiv($i, 3)) % 5, [0,2], true) ? 'bg-slate-800' : 'bg-white') }} block"></span>
                                    @endfor
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-bucha border border-slate-300 bg-black text-white px-4 py-2.5">
                                    <p class="text-[10px] uppercase tracking-wider text-white/70">{{ __('Download on the') }}</p>
                                    <p class="text-sm font-semibold">{{ __('App Store') }}</p>
                                </div>
                                <div class="rounded-bucha border border-slate-300 bg-black text-white px-4 py-2.5">
                                    <p class="text-[10px] uppercase tracking-wider text-white/70">{{ __('Get it on') }}</p>
                                    <p class="text-sm font-semibold">{{ __('Google Play') }}</p>
                                </div>
                                <a href="#final-cta" class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-sm font-semibold tracking-wide transition-colors">
                                    {{ __('Get the App') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- WHAT IS BUCHAPRO --}}
        <section id="what-is-buchapro" class="py-14 sm:py-16 bg-bucha-canvas border-y border-slate-200/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14 xl:gap-16 items-center">
                    <div class="text-center lg:text-left">
                        <p class="text-xs uppercase tracking-wider font-semibold text-bucha-muted">{{ __('What is BuchaPro') }}</p>
                        <h2 class="mt-3 text-2xl sm:text-3xl xl:text-4xl font-bold text-slate-900 tracking-tight">
                            {{ __('Redefining the Meat Value Chain') }}
                        </h2>
                        <p class="mt-4 text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl mx-auto lg:mx-0">
                            {{ __('BuchaPro is a digital and logistics platform that ensures meat is traceable, certified, and safely transported through a verifiable chain of custody.') }}
                        </p>
                    </div>
                    <div class="relative">
                        <div class="absolute -inset-3 sm:-inset-4 rounded-[24px] bg-gradient-to-br from-bucha-primary/15 via-emerald-500/10 to-slate-200/40 blur-2xl" aria-hidden="true"></div>
                        <figure class="relative overflow-hidden rounded-[20px] border border-slate-200/80 bg-white shadow-bucha aspect-[4/3] sm:aspect-[16/10]">
                            <img
                                src="{{ asset('images/buchapro-cows-farm.png') }}"
                                alt="{{ __('Cattle on a farm — traceable livestock and verified chain of custody') }}"
                                class="absolute inset-0 h-full w-full object-cover object-center"
                                width="1200"
                                height="800"
                                loading="lazy"
                                decoding="async"
                            />
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        {{-- HOW IT WORKS --}}
        <section id="how-it-works" class="py-14 sm:py-16 bg-white border-y border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-xs uppercase tracking-wider font-semibold text-bucha-muted text-center">{{ __('How it works') }}</p>
                <h2 class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900 text-center">{{ __('From Source to Table: A Chain of Custody') }}</h2>
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                    @foreach ([
                        ['title' => __('Source'), 'desc' => __('Livestock registration')],
                        ['title' => __('Process'), 'desc' => __('Certified facilities')],
                        ['title' => __('Transport'), 'desc' => __('Cold-chain logistics')],
                        ['title' => __('Verify'), 'desc' => __('Inspection & grading')],
                        ['title' => __('Deliver'), 'desc' => __('Traceable product')],
                    ] as $step)
                        <div class="rounded-bucha border border-slate-200/80 bg-slate-50 p-4 text-center">
                            <span class="mx-auto mb-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary text-white text-xs font-bold">{{ $loop->iteration }}</span>
                            <p class="text-sm font-semibold text-slate-900">{{ $step['title'] }}</p>
                            <p class="mt-1 text-xs text-slate-600">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ECOSYSTEM --}}
        <section id="ecosystem" class="py-14 sm:py-16 bg-bucha-canvas">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-xs uppercase tracking-wider font-semibold text-bucha-muted text-center">{{ __('The ecosystem') }}</p>
                <h2 class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900 text-center">{{ __('Solutions for Every Stakeholder') }}</h2>
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach ([
                        ['name' => __('Farmers'), 'slug' => 'farmers', 'desc' => __('Prove livestock origin and quality to access better markets.'), 'image' => 'https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => __('Processors'), 'slug' => 'processors', 'desc' => __('Automate compliance and batch tracking in one workflow.'), 'image' => 'https://images.unsplash.com/photo-1588168333986-5078d3ae3976?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => __('Logistics'), 'slug' => 'logistics', 'desc' => __('Run monitored cold-chain deliveries with clear records.'), 'image' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => __('Retailers'), 'slug' => 'retailers', 'desc' => __('Sell verified products with confidence and trust.'), 'image' => 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => __('Consumers'), 'slug' => 'consumers', 'desc' => __('Scan products and see traceable safety information.'), 'image' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?auto=format&fit=crop&w=1200&q=80'],
                    ] as $stakeholder)
                        <article class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-bucha">
                            <img src="{{ $stakeholder['image'] }}" alt="{{ $stakeholder['name'] }}" class="h-28 w-full object-cover rounded-bucha mb-3">
                            <h3 class="text-sm font-semibold text-slate-900">{{ $stakeholder['name'] }}</h3>
                            <p class="mt-2 text-xs text-slate-600 leading-relaxed">{{ $stakeholder['desc'] }}</p>
                            <a href="{{ route('ecosystem.show', $stakeholder['slug']) }}" class="mt-4 inline-flex items-center text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                {{ __('Learn More') }}
                            </a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- PLATFORM FEATURES --}}
        <section id="platform-features" class="py-14 sm:py-16 bg-white border-y border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-xs uppercase tracking-wider font-semibold text-bucha-muted text-center">{{ __('The Brain') }}</p>
                <h2 class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900 text-center">{{ __('Intelligence Behind the Infrastructure') }}</h2>
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ([
                        ['title' => __('GPS tracking'), 'desc' => __('Live shipment location visibility across routes.'), 'icon' => 'gps'],
                        ['title' => __('Temperature monitoring'), 'desc' => __('Cold-chain conditions tracked throughout transit.'), 'icon' => 'temperature'],
                        ['title' => __('Smart alerts'), 'desc' => __('Instant notifications for compliance deviations.'), 'icon' => 'alerts'],
                        ['title' => __('Inventory flow'), 'desc' => __('Seamless stock and batch movement records.'), 'icon' => 'inventory'],
                    ] as $feature)
                        <div class="rounded-bucha border border-slate-200/80 bg-slate-50 p-4">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-bucha-primary/10 text-bucha-primary mb-3">
                                @if ($feature['icon'] === 'gps')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s7-5.2 7-11a7 7 0 10-14 0c0 5.8 7 11 7 11z"/>
                                        <circle cx="12" cy="10" r="2.5" stroke-width="2"/>
                                    </svg>
                                @elseif ($feature['icon'] === 'temperature')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 14.8V5a2 2 0 10-4 0v9.8a4 4 0 104 0z"/>
                                    </svg>
                                @elseif ($feature['icon'] === 'alerts')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4a2 2 0 01-.6-1.4V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 7v11a2 2 0 002 2h8a2 2 0 002-2V7M9 11h6M9 15h4"/>
                                    </svg>
                                @endif
                            </span>
                            <h3 class="text-sm font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                            <p class="mt-1 text-xs text-slate-600">{{ $feature['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CERTIFICATION --}}
        <section id="certification" class="py-14 sm:py-16 bg-bucha-canvas">
            <div class="max-w-5xl mx-auto px-4 sm:px-6">
                <p class="text-xs uppercase tracking-wider font-semibold text-bucha-muted text-center">{{ __('Certification System') }}</p>
                <h2 class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900 text-center">{{ __('A Tiered System of Excellence') }}</h2>
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="rounded-bucha border border-bucha-primary/30 bg-white p-5 text-center shadow-bucha">
                        <p class="text-xs uppercase tracking-wider text-bucha-muted">{{ __('Grade') }}</p>
                        <p class="mt-1 text-2xl font-bold text-bucha-primary">A+</p>
                        <p class="mt-1 text-xs text-slate-600">{{ __('Export Ready') }}</p>
                    </div>
                    <div class="rounded-bucha border border-slate-200/80 bg-white p-5 text-center shadow-bucha">
                        <p class="text-xs uppercase tracking-wider text-bucha-muted">{{ __('Grade') }}</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">A</p>
                        <p class="mt-1 text-xs text-slate-600">{{ __('Premium') }}</p>
                    </div>
                    <div class="rounded-bucha border border-slate-200/80 bg-white p-5 text-center shadow-bucha">
                        <p class="text-xs uppercase tracking-wider text-bucha-muted">{{ __('Grade') }}</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">B</p>
                        <p class="mt-1 text-xs text-slate-600">{{ __('Verified') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- PRODUCTS --}}
        <section id="products" class="py-14 sm:py-16 bg-white border-y border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                <p class="text-xs uppercase tracking-wider font-semibold text-bucha-muted">{{ __('BuchaPro Foods') }}</p>
                <h2 class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900">{{ __('Premium Protein, Delivered Fresh') }}</h2>
                <p class="mt-4 text-sm text-slate-600">{{ __('Beef • Poultry • Goat • Fish') }}</p>
                </div>

                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach ([
                        ['name' => 'Prime Beef Cuts', 'category' => 'Beef', 'price' => '18,500', 'unit' => 'kg', 'badge' => 'Best Seller', 'image' => 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => 'Fresh Goat Meat', 'category' => 'Goat', 'price' => '13,000', 'unit' => 'kg', 'badge' => 'Popular', 'image' => 'https://images.unsplash.com/photo-1559561853-08451507cbe7?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => 'Whole Chicken', 'category' => 'Poultry', 'price' => '9,500', 'unit' => 'kg', 'badge' => 'Farm Fresh', 'image' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => 'Tilapia Fillet', 'category' => 'Fish', 'price' => '11,000', 'unit' => 'kg', 'badge' => 'New', 'image' => 'https://images.unsplash.com/photo-1510130387422-82bed34b37e9?auto=format&fit=crop&w=1200&q=80'],
                    ] as $product)
                        <article class="rounded-[18px] border border-slate-200/80 bg-white shadow-bucha overflow-hidden">
                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="h-36 w-full object-cover">
                            <div class="p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900">{{ $product['name'] }}</h3>
                                    <span class="text-[10px] rounded-full bg-bucha-primary/10 text-bucha-primary px-2 py-1 font-semibold">{{ __($product['badge']) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ __($product['category']) }}</p>
                                <p class="mt-3 text-lg font-bold text-bucha-primary">RWF {{ $product['price'] }} <span class="text-xs text-slate-500 font-medium">/ {{ $product['unit'] }}</span></p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ route('shop.index') }}" class="inline-flex items-center px-5 py-3 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-sm font-semibold transition-colors">
                        {{ __('Open Full Shop') }}
                    </a>
                </div>
            </div>
        </section>

        {{-- FINAL CTA --}}
        <section id="final-cta" class="py-14 sm:py-16 bg-gradient-to-br from-bucha-charcoal to-bucha-primary">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
                <h2 class="text-2xl sm:text-4xl font-bold text-white">{{ __('Join the future of meat integrity') }}</h2>
                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <a href="#mobile-platform" class="inline-flex items-center px-5 py-3 rounded-bucha bg-white text-bucha-primary hover:bg-slate-100 font-semibold text-sm tracking-wide transition-colors">
                        {{ __('Get the App') }}
                    </a>
                    <a href="#ecosystem" class="inline-flex items-center px-5 py-3 rounded-bucha border border-white/30 bg-white/10 text-white hover:bg-white/20 font-semibold text-sm tracking-wide transition-colors">
                        {{ __('Become a Partner') }}
                    </a>
                </div>
            </div>
        </section>
    </main>
    @include('layouts.footer')
</body>
</html>
