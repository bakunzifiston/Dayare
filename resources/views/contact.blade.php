<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Contact Us') }} - {{ config('app.name', 'BuchaPro') }}</title>
    @include('partials.site-favicon', ['pwa' => true])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bucha-canvas font-sans antialiased text-slate-900">
    <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <x-sidebar-brand href="{{ route('home') }}" theme="light" />
            <div class="flex items-center gap-4">
                <nav class="hidden items-center gap-2 border-r border-slate-200 pr-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 lg:flex">
                    <span>{{ __('Legal') }}</span>
                    <a href="{{ route('privacy-policy') }}" class="text-[11px] tracking-[0.12em] text-slate-600 transition-colors hover:text-bucha-primary">{{ __('Privacy Policy') }}</a>
                </nav>
                <a href="{{ route('home') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold transition-colors hover:bg-slate-50">
                    {{ __('Back to Home') }}
                </a>
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden bg-gradient-to-br from-bucha-charcoal via-bucha-sidebar to-bucha-primary">
            <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/75">{{ __('Contact BuchaPro') }}</p>
                    <h1 class="mt-3 text-3xl font-bold text-white sm:text-5xl">{{ __('Let’s discuss your operational goals') }}</h1>
                    <p class="mt-5 max-w-2xl text-sm leading-relaxed text-white/90 sm:text-base">
                        {{ __('Whether you are exploring deployment, mobile access, inspections, logistics workflows, or partnership opportunities, the BuchaPro team is ready to help you evaluate the right next step.') }}
                    </p>
                </div>
            </div>
        </section>

        <section class="py-12 sm:py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                    <div class="space-y-4">
                        <div class="rounded-[20px] border border-slate-200/80 bg-white p-6 shadow-bucha">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-bucha-muted">{{ __('Email') }}</p>
                            <a href="mailto:support@buchapro.com" class="mt-3 block text-base font-semibold text-bucha-primary hover:text-bucha-burgundy">support@buchapro.com</a>
                            <p class="mt-2 text-sm text-slate-600">{{ __('Best for partnership, implementation, and product questions.') }}</p>
                        </div>

                        <div class="rounded-[20px] border border-slate-200/80 bg-white p-6 shadow-bucha">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-bucha-muted">{{ __('Call us') }}</p>
                            <a href="tel:+250783092757" class="mt-3 block text-base font-semibold text-slate-900 hover:text-bucha-primary">+250 783 092 757</a>
                            <p class="mt-2 text-sm text-slate-600">{{ __('Mon - Fri, 8:00 AM - 6:00 PM') }}</p>
                        </div>

                        <div class="rounded-[20px] border border-slate-200/80 bg-white p-6 shadow-bucha">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-bucha-muted">{{ __('Visit') }}</p>
                            <p class="mt-3 text-base font-semibold text-slate-900">44 KG 548 St, Kigali, Rwanda</p>
                            <p class="mt-2 text-sm text-slate-600">{{ __('Meet the team for operational walkthroughs, deployment planning, and ecosystem discussions.') }}</p>
                        </div>
                    </div>

                    <div class="rounded-[24px] border border-slate-200/80 bg-white p-6 shadow-bucha sm:p-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-bucha-muted">{{ __('Professional enquiries') }}</p>
                        <h2 class="mt-3 text-2xl font-bold text-slate-900">{{ __('Tell us what you need') }}</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">
                            {{ __('For the strongest response, include your organization type, the workflows you want to improve, and whether you are interested in deployment, mobile access, compliance operations, or partner onboarding.') }}
                        </p>

                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('Common topics') }}</p>
                                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                                    <li>{{ __('Processor workflow deployment') }}</li>
                                    <li>{{ __('Cold-chain and transport visibility') }}</li>
                                    <li>{{ __('Mobile access for field teams') }}</li>
                                    <li>{{ __('Stakeholder workspace onboarding') }}</li>
                                </ul>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('Recommended next step') }}</p>
                                <p class="mt-3 text-sm leading-relaxed text-slate-700">
                                    {{ __('Email the team with your organization name, country, and operational goals, or call directly for a guided discussion.') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl border border-bucha-primary/15 bg-bucha-primary/5 p-5">
                            <p class="text-sm font-semibold text-slate-900">{{ __('Ready to speak with the team?') }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('Use the direct contact options below to request a demo, discuss implementation, or ask about the right BuchaPro workflow for your organization.') }}</p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="mailto:support@buchapro.com?subject={{ rawurlencode('BuchaPro enquiry') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-5 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-bucha-burgundy">
                                    {{ __('Email the team') }}
                                </a>
                                <a href="tel:+250783092757" class="inline-flex items-center rounded-bucha border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                                    {{ __('Call now') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')
    @include('partials.pwa-install-prompt')
</body>
</html>
