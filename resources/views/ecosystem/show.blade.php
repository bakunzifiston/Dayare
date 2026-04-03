<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page['title'] }} - {{ config('app.name', 'BuchaPro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas text-slate-900">
    <header class="sticky top-0 z-20 bg-white/95 border-b border-slate-200/80">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <x-sidebar-brand href="{{ route('home') }}" theme="light" />
            <a href="{{ route('home') }}#ecosystem" class="inline-flex items-center px-4 py-2 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold">
                {{ __('Back to Ecosystem') }}
            </a>
        </div>
    </header>

    <main>
        @if(in_array(($audience ?? ''), ['farmers', 'processors', 'logistics', 'retailers', 'consumers'], true))
            {{-- Rich marketing pages (ecosystem detail) --}}
            <section class="relative overflow-hidden bg-gradient-to-br from-bucha-charcoal via-bucha-sidebar to-bucha-primary">
                <img
                    src="{{ $page['hero_image'] }}"
                    alt=""
                    class="absolute inset-0 w-full h-full object-cover opacity-25"
                />
                <div class="absolute inset-0 bg-gradient-to-r from-bucha-charcoal/95 via-bucha-charcoal/85 to-bucha-primary/70"></div>
                <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20">
                    <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
                        <div>
                            <p class="text-xs uppercase tracking-wider font-semibold text-white/80">{{ __('Ecosystem') }} · {{ $page['breadcrumb'] }}</p>
                            <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight">{{ $page['title'] }}</h1>
                            <p class="mt-4 text-lg sm:text-xl font-semibold text-white">{{ $page['subtitle'] }}</p>
                            <p class="mt-4 text-white/90 text-sm sm:text-base leading-relaxed max-w-xl">{{ $page['intro'] }}</p>
                            @if(filled($page['cta_primary'] ?? null) || filled($page['cta_secondary'] ?? null))
                                <div class="mt-8 flex flex-wrap gap-3">
                                    @if(filled($page['cta_primary'] ?? null))
                                        <a href="{{ $page['cta_primary_href'] ?? route('register') }}" class="inline-flex items-center px-5 py-3 rounded-bucha bg-white text-bucha-primary hover:bg-slate-100 text-sm font-semibold tracking-wide transition-colors">
                                            {{ $page['cta_primary'] }}
                                        </a>
                                    @endif
                                    @if(filled($page['cta_secondary'] ?? null))
                                        <a href="{{ route('contact-us') }}" class="inline-flex items-center px-5 py-3 rounded-bucha border border-white/40 bg-white/10 text-white hover:bg-white/20 text-sm font-semibold tracking-wide transition-colors">
                                            {{ $page['cta_secondary'] }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="hidden lg:block rounded-[20px] overflow-hidden border border-white/20 shadow-2xl ring-1 ring-white/10">
                            <img src="{{ $page['hero_image'] }}" alt="@if(($audience ?? '') === 'processors'){{ __('Meat processing') }}@elseif(($audience ?? '') === 'logistics'){{ __('Cold-chain logistics') }}@elseif(($audience ?? '') === 'retailers'){{ __('Retail meat counter') }}@elseif(($audience ?? '') === 'consumers'){{ __('Fresh meat and groceries') }}@else{{ __('Cattle on a farm') }}@endif" class="w-full h-[340px] object-cover">
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-12 sm:py-16 bg-white border-b border-slate-200/80">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
                        <div class="order-2 lg:order-1 rounded-[18px] overflow-hidden border border-slate-200/80 shadow-bucha">
                            <img src="{{ $page['why_image'] }}" alt="@if(($audience ?? '') === 'processors'){{ __('Premium meat processing') }}@elseif(($audience ?? '') === 'logistics'){{ __('Transport and delivery') }}@elseif(($audience ?? '') === 'retailers'){{ __('Quality meat products') }}@elseif(($audience ?? '') === 'consumers'){{ __('Quality meat for your table') }}@else{{ __('Livestock') }}@endif" class="w-full h-64 sm:h-80 object-cover">
                        </div>
                        <div class="order-1 lg:order-2">
                            <p class="text-xs uppercase tracking-wider font-semibold text-bucha-muted">{{ $page['why_heading'] }}</p>
                            <div class="mt-6 space-y-6">
                                @foreach ($page['why'] as $item)
                                    <div>
                                        @if(filled($item['body'] ?? null))
                                            <h3 class="text-base font-bold text-slate-900">{{ $item['title'] }}</h3>
                                            <p class="mt-1 text-sm text-slate-600 leading-relaxed">{{ $item['body'] }}</p>
                                        @else
                                            <p class="text-base font-semibold text-slate-900 leading-relaxed">{{ $item['title'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-12 sm:py-16 bg-bucha-canvas">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-2xl mx-auto">
                        <p class="text-xs uppercase tracking-wider font-semibold text-bucha-muted">{{ $page['how_heading'] }}</p>
                        <h2 class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900">{{ $page['how_subheading'] }}</h2>
                    </div>
                    <div class="mt-10 grid lg:grid-cols-2 gap-8 items-start">
                        <div class="rounded-[18px] overflow-hidden border border-slate-200/80 shadow-bucha bg-white">
                            <img src="{{ $page['works_image'] }}" alt="@if(($audience ?? '') === 'processors'){{ __('Food quality and traceability') }}@elseif(($audience ?? '') === 'logistics'){{ __('Fleet and freight operations') }}@elseif(($audience ?? '') === 'retailers'){{ __('Fresh retail sourcing') }}@elseif(($audience ?? '') === 'consumers'){{ __('Shop and order on your phone') }}@else{{ __('Cattle') }}@endif" class="w-full h-48 sm:h-56 object-cover">
                            <div class="p-6 sm:p-8">
                                <ol class="space-y-6">
                                    @foreach ($page['steps'] as $i => $step)
                                        <li class="flex gap-4">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-bucha-primary text-white text-sm font-bold">{{ $i + 1 }}</span>
                                            <div>
                                                <h3 class="font-semibold text-slate-900">{{ $step['title'] }}</h3>
                                                <p class="mt-1 text-sm text-slate-600 leading-relaxed">{{ $step['body'] }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                        <div class="rounded-[18px] border border-slate-200/80 bg-white p-6 sm:p-8 shadow-bucha overflow-hidden">
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2 rounded-bucha overflow-hidden border border-slate-200/80">
                                    <img src="{{ $page['trust_image'] }}" alt="@if(($audience ?? '') === 'processors'){{ __('Operations and accountability') }}@elseif(($audience ?? '') === 'logistics'){{ __('Verified logistics and freight') }}@elseif(($audience ?? '') === 'retailers'){{ __('Trusted retail operations') }}@elseif(($audience ?? '') === 'consumers'){{ __('Fresh, transparent food') }}@else{{ __('Farm and pasture') }}@endif" class="w-full h-40 object-cover">
                                </div>
                                <div class="sm:col-span-2">
                                    <h3 class="text-lg font-bold text-slate-900">{{ __('🛡️ ') }}{{ $page['trust_title'] }}</h3>
                                    <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ $page['trust_body'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if(! empty($page['mobile_points'] ?? null))
                <section class="py-12 sm:py-16 bg-white border-y border-slate-200/80">
                    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="grid lg:grid-cols-2 gap-10 items-center">
                            <div>
                                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">{{ $page['mobile_title'] }}</h2>
                                <ul class="mt-6 space-y-3">
                                    @foreach ($page['mobile_points'] as $pt)
                                        <li class="flex items-center gap-3 text-sm text-slate-700">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            </span>
                                            {{ $pt }}
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-6 text-sm font-semibold text-bucha-primary">{{ $page['mobile_footer'] }}</p>
                            </div>
                            <div class="rounded-[18px] overflow-hidden border border-slate-200/80 shadow-bucha">
                                <img src="{{ $page['mobile_image'] }}" alt="{{ __('Farmer using mobile phone') }}" class="w-full h-72 sm:h-80 object-cover object-top">
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <section class="py-14 sm:py-20 bg-gradient-to-br from-bucha-charcoal to-bucha-primary">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center">
                    <h2 class="text-2xl sm:text-3xl font-bold text-white">@if($page['cta_show_rocket'] ?? true)<span aria-hidden="true">{{ __('🚀 ') }}</span>@endif{{ $page['cta_title'] }}</h2>
                    @if(! empty($page['cta_subtitle_paragraphs'] ?? null))
                        <div class="mt-4 space-y-4 text-white/90 text-sm sm:text-base leading-relaxed">
                            @foreach ($page['cta_subtitle_paragraphs'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-4 text-white/90 text-sm sm:text-base leading-relaxed">{{ $page['cta_subtitle'] }}</p>
                    @endif
                    @if(filled($page['cta_primary'] ?? null) || filled($page['cta_secondary'] ?? null))
                        <div class="mt-8 flex flex-wrap justify-center gap-3">
                            @if(filled($page['cta_primary'] ?? null))
                                <a href="{{ $page['cta_primary_href'] ?? route('register') }}" class="inline-flex items-center px-6 py-3 rounded-bucha bg-white text-bucha-primary hover:bg-slate-100 text-sm font-semibold transition-colors">
                                    {{ $page['cta_primary'] }}
                                </a>
                            @endif
                            @if(filled($page['cta_secondary'] ?? null))
                                <a href="{{ route('contact-us') }}" class="inline-flex items-center px-6 py-3 rounded-bucha border border-white/40 bg-white/10 text-white hover:bg-white/20 text-sm font-semibold transition-colors">
                                    {{ $page['cta_secondary'] }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </section>
        @else
            <section class="relative overflow-hidden bg-gradient-to-br from-bucha-charcoal via-bucha-sidebar to-bucha-primary">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, #ffffff 2px, transparent 2px); background-size: 24px 24px;"></div>
                <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
                    <p class="text-xs uppercase tracking-wider font-semibold text-white/80">{{ __('Ecosystem') }}</p>
                    <h1 class="mt-3 text-3xl sm:text-5xl font-bold text-white">{{ $page['title'] }}</h1>
                    <p class="mt-4 text-white/90 max-w-2xl text-sm sm:text-base leading-relaxed">{{ $page['subtitle'] }}</p>
                </div>
            </section>

            <section class="py-10 sm:py-14">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="rounded-[18px] border border-slate-200/80 bg-white p-6 sm:p-8 shadow-bucha">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900">{{ __('How BuchaPro helps') }}</h2>
                        <ul class="mt-5 space-y-3 text-sm sm:text-base text-slate-700">
                            @foreach ($page['content'] as $line)
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-bucha-primary"></span>
                                    <span>{{ $line }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('register') }}" class="inline-flex items-center px-5 py-3 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-sm font-semibold tracking-wide transition-colors">
                                {{ __('Become a Partner') }}
                            </a>
                            <a href="{{ route('contact-us') }}" class="inline-flex items-center px-5 py-3 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold tracking-wide transition-colors">
                                {{ __('Contact Us') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </main>
    @include('layouts.footer')
</body>
</html>
