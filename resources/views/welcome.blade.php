<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @include('partials.site-favicon', ['pwa' => true])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bucha-canvas font-sans antialiased text-slate-900">
    <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <x-sidebar-brand href="{{ route('home') }}" theme="light" />
            <nav class="hidden items-center gap-5 text-sm font-semibold text-slate-700 lg:flex">
                <a href="#mobile-platform" class="transition-colors hover:text-bucha-primary">{{ __('Mobile') }}</a>
                <a href="#what-is-buchapro" class="transition-colors hover:text-bucha-primary">{{ __('About') }}</a>
                <a href="#how-it-works" class="transition-colors hover:text-bucha-primary">{{ __('How it works') }}</a>
                <a href="#ecosystem" class="transition-colors hover:text-bucha-primary">{{ __('Ecosystem') }}</a>
                <a href="#platform-features" class="transition-colors hover:text-bucha-primary">{{ __('Features') }}</a>
                @if (config('features.shop'))
                    <a href="#products" class="transition-colors hover:text-bucha-primary">{{ __('Products') }}</a>
                @endif
                <a href="{{ route('contact-us') }}" class="transition-colors hover:text-bucha-primary">{{ __('Contact') }}</a>
                <div class="flex items-center gap-2 border-l border-slate-200 pl-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    <span>{{ __('Legal') }}</span>
                    <a href="{{ route('privacy-policy') }}" class="text-[11px] tracking-[0.12em] text-slate-600 transition-colors hover:text-bucha-primary">{{ __('Privacy Policy') }}</a>
                </div>
            </nav>
            <div class="flex items-center gap-2 sm:gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold tracking-wide text-white transition-colors hover:bg-bucha-burgundy sm:text-sm">
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-xs font-semibold tracking-wide transition-colors hover:bg-slate-50 sm:text-sm">
                            {{ __('Sign in') }}
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold tracking-wide text-white transition-colors hover:bg-bucha-burgundy sm:text-sm">
                                {{ __('Get started') }}
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden bg-gradient-to-br from-bucha-charcoal via-bucha-sidebar to-bucha-primary">
            <img
                src="{{ asset('images/Abattoir-For-Livestock-Meat-3a9cfc1f-683f-49ec-bec4-5aceb28cd5f5.png') }}"
                alt="{{ __('Meat integrity banner') }}"
                class="absolute inset-0 h-full w-full object-cover opacity-35"
            />
            <div class="relative z-10 mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,480px)]">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/85">
                            <span class="inline-block h-2 w-2 rounded-full bg-emerald-400"></span>
                            {{ __('Professional operations infrastructure') }}
                        </div>
                        <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-white sm:text-5xl">
                            {{ __('Operational integrity for the modern meat value chain') }}
                        </h1>
                        <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/90 sm:text-lg">
                            {{ __('BuchaPro equips meat value-chain operators with a professional system for traceability, compliance workflows, certificate readiness, cold-chain oversight, and operational trust.') }}
                        </p>

                        <div class="mt-7 grid max-w-3xl grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-white/65">{{ __('Traceability') }}</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ __('Source to delivery records') }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-white/65">{{ __('Compliance') }}</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ __('Inspection and certificate context') }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-white/65">{{ __('Cold-chain') }}</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ __('Transport and storage visibility') }}</p>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('contact-us') }}" class="inline-flex items-center rounded-bucha bg-white px-5 py-3 text-sm font-semibold tracking-wide text-bucha-primary transition-colors hover:bg-slate-100">
                                {{ __('Request a Consultation') }}
                            </a>
                            <a href="#mobile-platform" class="inline-flex items-center rounded-bucha border border-white/30 bg-white/10 px-5 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-white/20">
                                {{ __('Explore the Platform') }}
                            </a>
                            @if (config('features.shop'))
                                <a href="{{ route('shop.index') }}" class="inline-flex items-center rounded-bucha border border-white/30 bg-white/10 px-5 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-white/20">
                                    {{ __('Shop Now') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-[24px] border border-white/20 bg-white/10 p-5 shadow-xl sm:p-6">
                        <p class="mb-3 text-xs uppercase tracking-wider text-white/80">{{ __('Operations snapshot') }}</p>
                        <div class="rounded-[20px] border border-slate-200/80 bg-white/95 p-4 sm:p-5">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ __('BuchaPro Control Tower') }}</p>
                                    <p class="text-xs text-slate-500">{{ __('A unified operating view across traceability, compliance, and movement') }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">{{ __('Live') }}</span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('Workflow') }}</p>
                                    <p class="mt-1 text-lg font-bold text-slate-900">{{ __('Controlled') }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('Status') }}</p>
                                    <p class="mt-1 text-lg font-bold text-bucha-primary">{{ __('Assured') }}</p>
                                </div>
                                <div class="col-span-2 rounded-xl border border-slate-200 bg-white p-3">
                                    <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('Chain of custody') }}</p>
                                    <div class="mt-3 flex items-center justify-between gap-2 text-[11px] font-medium text-slate-600">
                                        <span>{{ __('Source') }}</span>
                                        <span>{{ __('Inspection') }}</span>
                                        <span>{{ __('Storage') }}</span>
                                        <span>{{ __('Transport') }}</span>
                                        <span>{{ __('Verify') }}</span>
                                    </div>
                                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full w-full rounded-full bg-gradient-to-r from-bucha-primary via-bucha-burgundy to-emerald-500"></div>
                                    </div>
                                    <p class="mt-3 text-xs text-slate-600">{{ __('Designed for teams that require stronger records, clearer governance, and confidence in every handoff.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="what-is-buchapro" class="border-y border-slate-200/60 bg-bucha-canvas py-14 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div class="text-center lg:text-left">
                        <p class="text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('What is BuchaPro') }}</p>
                        <h2 class="mt-3 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl xl:text-3xl">
                            {{ __('Professional infrastructure for the meat value chain') }}
                        </h2>
                        <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-600 sm:text-base lg:mx-0">
                            {{ __('BuchaPro is a digital and logistics platform that helps meat-sector organizations manage traceability, inspections, certificates, storage, transport, and stakeholder accountability through one consistent operational system.') }}
                        </p>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('Built for control') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Structured workflows from intake to dispatch') }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('Built for trust') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Auditable records across batches, certificates, and transport') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="absolute -inset-3 rounded-[24px] bg-gradient-to-br from-bucha-primary/15 via-emerald-500/10 to-slate-200/40 blur-2xl sm:-inset-4" aria-hidden="true"></div>
                        <figure class="relative aspect-[4/3] overflow-hidden rounded-[20px] border border-slate-200/80 bg-white shadow-bucha sm:aspect-[16/10]">
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

        <section id="how-it-works" class="border-y border-slate-200/80 bg-white py-14 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-center text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('How it works') }}</p>
                <h2 class="mt-3 text-center text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('From source to table, every step is documented') }}</h2>
                <p class="mx-auto mt-4 max-w-3xl text-center text-sm leading-relaxed text-slate-600 sm:text-base">
                    {{ __('BuchaPro standardizes the flow of data across sourcing, inspection, storage, transport, and verification so every stakeholder operates with clearer visibility and stronger accountability.') }}
                </p>
                <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ([
                        ['title' => __('Source'), 'desc' => __('Animal intake, identity, and origin are captured in a structured record.')],
                        ['title' => __('Process'), 'desc' => __('Facility workflows and batch operations are documented in consistent steps.')],
                        ['title' => __('Transport'), 'desc' => __('Cold-chain movements are monitored and recorded through transit.')],
                        ['title' => __('Verify'), 'desc' => __('Inspection and certificate context support trusted operational decisions.')],
                        ['title' => __('Deliver'), 'desc' => __('Partners and buyers receive traceable, verifiable product information.')],
                    ] as $step)
                        <div class="rounded-bucha border border-slate-200/80 bg-slate-50 p-5 text-center shadow-sm">
                            <span class="mx-auto mb-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary text-xs font-bold text-white">{{ $loop->iteration }}</span>
                            <p class="text-sm font-semibold text-slate-900">{{ $step['title'] }}</p>
                            <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="mobile-platform" class="border-y border-slate-200/80 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-18 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-12">
                    <div class="order-1 flex justify-center lg:order-2">
                        <div class="relative">
                            <div class="absolute -inset-4 rounded-[28px] bg-gradient-to-br from-bucha-primary/20 to-bucha-charcoal/10 blur-2xl sm:-inset-6"></div>
                            <img
                                src="{{ asset('images/buchapro-mobile-showcase.png') }}"
                                alt="{{ __('BuchaPro mobile app screens') }}"
                                class="relative w-full max-w-[560px] rounded-[22px] border border-slate-200/80 shadow-2xl"
                            >
                            <div class="absolute -bottom-3 -right-2 rounded-bucha border border-slate-200 bg-white px-3 py-2 shadow-bucha sm:-bottom-4 sm:-right-4">
                                <p class="text-[11px] uppercase tracking-wider text-bucha-muted">{{ __('Mobile access') }}</p>
                                <p class="text-xs font-semibold text-slate-700">{{ __('Field operations • inspections • logistics') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="order-2 lg:order-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('Mobile Platform') }}</p>
                        <h2 class="mt-3 text-2xl font-bold text-slate-900 sm:text-4xl">{{ __('Mobile access for field teams and operational leadership') }}</h2>
                        <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-600 sm:text-base">
                            {{ __('BuchaPro extends operational visibility beyond the office by giving authorized teams access to shipment progress, traceability records, compliance checkpoints, and verification context wherever work happens.') }}
                        </p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('Visibility') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Movement context') }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('Operations') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Field inspection support') }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('Trust') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Shared data across teams') }}</p>
                            </div>
                        </div>

                        <div class="mt-7 grid grid-cols-1 gap-4 sm:grid-cols-[210px_1fr] sm:gap-6">
                            <div class="rounded-bucha border border-slate-200 bg-slate-50 p-4">
                                <p class="text-[11px] uppercase tracking-wider text-bucha-muted">{{ __('Deployment ready') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ __('Provisioned per organization') }}</p>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ __('Mobile access is provisioned based on your operational setup, user roles, and deployment workflow.') }}</p>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-[10px] uppercase tracking-wider text-slate-500">{{ __('Best for') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Inspectors, transport teams, operations managers, verification workflows') }}</p>
                                </div>
                                <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-[10px] uppercase tracking-wider text-slate-500">{{ __('Next step') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Speak with BuchaPro to enable the right mobile experience for your team') }}</p>
                                </div>
                                <a href="{{ route('contact-us') }}" class="inline-flex w-full items-center justify-center rounded-bucha bg-bucha-primary px-5 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-bucha-burgundy sm:w-auto">
                                    {{ __('Request Mobile Access') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="ecosystem" class="bg-bucha-canvas py-14 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-center text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('The ecosystem') }}</p>
                <h2 class="mt-3 text-center text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('Solutions for every stakeholder in the chain') }}</h2>
                <p class="mx-auto mt-4 max-w-3xl text-center text-sm leading-relaxed text-slate-600 sm:text-base">
                    {{ __('Each BuchaPro workspace is designed around the responsibilities, controls, and visibility required by a specific participant in the meat value chain.') }}
                </p>
                <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-5">
                    @foreach ([
                        ['name' => __('Farmers'), 'slug' => 'farmers', 'desc' => __('Prove livestock origin and quality to access better markets.'), 'image' => 'https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => __('Processors'), 'slug' => 'processors', 'desc' => __('Automate compliance and batch tracking in one workflow.'), 'image' => 'https://images.unsplash.com/photo-1588168333986-5078d3ae3976?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => __('Logistics'), 'slug' => 'logistics', 'desc' => __('Run monitored cold-chain deliveries with clear records.'), 'image' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => __('Retailers'), 'slug' => 'retailers', 'desc' => __('Sell verified products with confidence and trust.'), 'image' => 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => __('Consumers'), 'slug' => 'consumers', 'desc' => __('Scan products and see traceable safety information.'), 'image' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?auto=format&fit=crop&w=1200&q=80'],
                    ] as $stakeholder)
                        <article class="overflow-hidden rounded-[20px] border border-slate-200/80 bg-white shadow-bucha">
                            <img src="{{ $stakeholder['image'] }}" alt="{{ $stakeholder['name'] }}" class="h-32 w-full object-cover">
                            <div class="p-5">
                                <h3 class="text-sm font-semibold text-slate-900">{{ $stakeholder['name'] }}</h3>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $stakeholder['desc'] }}</p>
                                <a href="{{ route('ecosystem.show', $stakeholder['slug']) }}" class="mt-4 inline-flex items-center text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                    {{ __('Explore workspace') }}
                                    <span class="ml-1" aria-hidden="true">→</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="platform-features" class="border-y border-slate-200/80 bg-white py-14 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-center text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('Platform capabilities') }}</p>
                <h2 class="mt-3 text-center text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('Operational intelligence behind the workflow') }}</h2>
                <p class="mx-auto mt-4 max-w-3xl text-center text-sm leading-relaxed text-slate-600 sm:text-base">
                    {{ __('BuchaPro helps teams manage operational detail without losing executive visibility, audit readiness, or customer trust.') }}
                </p>
                <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['title' => __('Traceability records'), 'desc' => __('Linked records for source, batch, certificate, storage, and delivery activity.'), 'icon' => 'gps'],
                        ['title' => __('Temperature monitoring'), 'desc' => __('Cold-chain conditions tracked across transport and storage workflows.'), 'icon' => 'temperature'],
                        ['title' => __('Compliance alerts'), 'desc' => __('Clear visibility into deviations, missing approvals, and operational exceptions.'), 'icon' => 'alerts'],
                        ['title' => __('Certificate workflows'), 'desc' => __('Certificate readiness supported by better inspection and release context.'), 'icon' => 'inventory'],
                        ['title' => __('Multi-role workspaces'), 'desc' => __('Purpose-built experiences for farmers, processors, logistics teams, retailers, and consumers.'), 'icon' => 'alerts'],
                        ['title' => __('Management visibility'), 'desc' => __('A clearer executive view of workflows, records, and operational movement.'), 'icon' => 'inventory'],
                    ] as $feature)
                        <div class="rounded-[20px] border border-slate-200/80 bg-slate-50 p-5">
                            <span class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-full bg-bucha-primary/10 text-bucha-primary">
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
                            <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $feature['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        @if (config('features.shop'))
        <section id="products" class="border-y border-slate-200/80 bg-white py-14 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('BuchaPro Foods') }}</p>
                    <h2 class="mt-3 text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('Premium protein, delivered fresh') }}</h2>
                    <p class="mt-4 text-sm text-slate-600">{{ __('Beef • Poultry • Goat • Fish') }}</p>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['name' => 'Prime Beef Cuts', 'category' => 'Beef', 'price' => '18,500', 'unit' => 'kg', 'badge' => 'Best Seller', 'image' => 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => 'Fresh Goat Meat', 'category' => 'Goat', 'price' => '13,000', 'unit' => 'kg', 'badge' => 'Popular', 'image' => 'https://images.unsplash.com/photo-1559561853-08451507cbe7?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => 'Whole Chicken', 'category' => 'Poultry', 'price' => '9,500', 'unit' => 'kg', 'badge' => 'Farm Fresh', 'image' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=1200&q=80'],
                        ['name' => 'Tilapia Fillet', 'category' => 'Fish', 'price' => '11,000', 'unit' => 'kg', 'badge' => 'New', 'image' => 'https://images.unsplash.com/photo-1510130387422-82bed34b37e9?auto=format&fit=crop&w=1200&q=80'],
                    ] as $product)
                        <article class="overflow-hidden rounded-[18px] border border-slate-200/80 bg-white shadow-bucha">
                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="h-36 w-full object-cover">
                            <div class="p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900">{{ $product['name'] }}</h3>
                                    <span class="rounded-full bg-bucha-primary/10 px-2 py-1 text-[10px] font-semibold text-bucha-primary">{{ __($product['badge']) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ __($product['category']) }}</p>
                                <p class="mt-3 text-lg font-bold text-bucha-primary">RWF {{ $product['price'] }} <span class="text-xs font-medium text-slate-500">/ {{ $product['unit'] }}</span></p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ route('shop.index') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-bucha-burgundy">
                        {{ __('Open Full Shop') }}
                    </a>
                </div>
            </div>
        </section>
        @endif

        <section id="final-cta" class="bg-gradient-to-br from-bucha-charcoal to-bucha-primary py-14 sm:py-16">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/70">{{ __('Ready to modernize your operation?') }}</p>
                <h2 class="mt-3 text-2xl font-bold text-white sm:text-4xl">{{ __('Bring professional traceability and control to your meat value chain') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-white/85 sm:text-base">
                    {{ __('Talk to the BuchaPro team about deployment, mobile access, onboarding, and the right workflow configuration for your organization.') }}
                </p>
                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('contact-us') }}" class="inline-flex items-center rounded-bucha bg-white px-5 py-3 text-sm font-semibold tracking-wide text-bucha-primary transition-colors hover:bg-slate-100">
                        {{ __('Request a Consultation') }}
                    </a>
                    <a href="#ecosystem" class="inline-flex items-center rounded-bucha border border-white/30 bg-white/10 px-5 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-white/20">
                        {{ __('Explore the Ecosystem') }}
                    </a>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')

    <x-whatsapp-float phone="250785171213" />
    @include('partials.pwa-install-prompt')
</body>
</html>
