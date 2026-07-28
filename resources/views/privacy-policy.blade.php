<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Privacy Policy') }} - {{ config('app.name', 'BuchaPro') }}</title>
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
                    <a href="{{ route('privacy-policy') }}" class="text-[11px] tracking-[0.12em] text-bucha-primary">{{ __('Privacy Policy') }}</a>
                </nav>
                <div class="flex items-center gap-2">
                <a href="{{ route('home') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold transition-colors hover:bg-slate-50">
                    {{ __('Back to Home') }}
                </a>
                <a href="{{ route('contact-us') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-bucha-burgundy">
                    {{ __('Contact Us') }}
                </a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden bg-gradient-to-br from-bucha-charcoal via-bucha-sidebar to-bucha-primary">
            <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-end">
                    <div class="max-w-4xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/75">{{ __('Legal Information') }}</p>
                        <h1 class="mt-3 text-3xl font-bold text-white sm:text-5xl">{{ __('Privacy Policy') }}</h1>
                        <p class="mt-5 max-w-3xl text-sm leading-relaxed text-white/90 sm:text-base">
                            {{ __('This page explains how BuchaPro handles privacy, what information may be collected and used, and the basic conditions that apply when users access our website and platform services.') }}
                        </p>
                        <p class="mt-4 text-sm text-white/75">
                            {{ __('Last updated:') }} {{ now()->format('F j, Y') }}
                        </p>
                    </div>

                    <div class="rounded-[24px] border border-white/15 bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">{{ __('Quick summary') }}</p>
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.18em] text-white/60">{{ __('Privacy') }}</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ __('How data may be collected, used, protected, and retained') }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.18em] text-white/60">{{ __('Use') }}</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ __('Rules for lawful and authorized use of the platform') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-12 sm:py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[0.82fr_1.18fr]">
                    <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                        <div class="rounded-[20px] border border-slate-200/80 bg-white p-6 shadow-bucha">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-bucha-muted">{{ __('At a glance') }}</p>
                            <ul class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                                <li>{{ __('We collect information needed to provide, secure, and improve BuchaPro services.') }}</li>
                                <li>{{ __('Users and organizations remain responsible for the accuracy and lawful submission of their operational data.') }}</li>
                                <li>{{ __('We may use technical, administrative, and organizational measures to protect information.') }}</li>
                                <li>{{ __('Use of BuchaPro is also subject to the platform rules and conditions described on this same page.') }}</li>
                            </ul>
                        </div>

                        <div class="rounded-[20px] border border-slate-200/80 bg-slate-50/90 p-6 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-bucha-muted">{{ __('This page covers') }}</p>
                            <div class="mt-4 space-y-2 text-sm text-slate-600">
                                <p>{{ __('Data collection and usage') }}</p>
                                <p>{{ __('Security and retention') }}</p>
                                <p>{{ __('User responsibilities') }}</p>
                                <p>{{ __('Contact and support') }}</p>
                            </div>
                        </div>

                        <div class="rounded-[20px] border border-slate-200/80 bg-white p-6 shadow-bucha">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-bucha-muted">{{ __('Contact for privacy matters') }}</p>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ __('If you have a privacy-related question, concern, or request, contact BuchaPro directly and include enough detail for our team to understand the issue.') }}
                            </p>
                            <div class="mt-4 space-y-2 text-sm">
                                <a href="mailto:support@buchapro.com" class="block font-semibold text-bucha-primary hover:text-bucha-burgundy">support@buchapro.com</a>
                                <a href="tel:+250783092757" class="block font-semibold text-slate-900 hover:text-bucha-primary">+250 783 092 757</a>
                            </div>
                        </div>
                    </aside>

                    <div class="space-y-4">
                        <section class="rounded-[24px] border border-slate-200/80 bg-white p-6 shadow-bucha sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary/10 text-sm font-bold text-bucha-primary">1</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('Information we may collect') }}</h2>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ __('Depending on how BuchaPro is used, we may collect contact details, account information, operational records, verification data, transaction details, support enquiries, and technical information such as browser or device activity needed to deliver the service.') }}
                            </p>
                        </section>

                        <section class="rounded-[24px] border border-slate-200/80 bg-white p-6 shadow-bucha sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary/10 text-sm font-bold text-bucha-primary">2</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('How information may be used') }}</h2>
                            </div>
                            <ul class="mt-3 space-y-3 text-sm leading-relaxed text-slate-600">
                                <li>{{ __('To operate, maintain, and improve BuchaPro and related workflows.') }}</li>
                                <li>{{ __('To support traceability, compliance, verification, logistics, and customer service processes.') }}</li>
                                <li>{{ __('To protect platform security, monitor misuse, and support legitimate business or regulatory requirements.') }}</li>
                                <li>{{ __('To communicate with users or organizations about enquiries, support, updates, and service matters.') }}</li>
                            </ul>
                        </section>

                        <section class="rounded-[24px] border border-slate-200/80 bg-white p-6 shadow-bucha sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary/10 text-sm font-bold text-bucha-primary">3</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('Data provided by organizations and users') }}</h2>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ __('Many records in BuchaPro are submitted by users, teams, or organizations. Those parties are responsible for ensuring that the information they submit is accurate, properly authorized, and handled in line with applicable laws, policies, and contractual duties.') }}
                            </p>
                        </section>

                        <section class="rounded-[24px] border border-slate-200/80 bg-white p-6 shadow-bucha sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary/10 text-sm font-bold text-bucha-primary">4</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('Sharing of information') }}</h2>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ __('Information may be shared where necessary to provide services, support verification workflows, comply with legal obligations, protect rights and security, or work with service providers acting on our behalf. We do not describe public-facing data use beyond what is necessary for the service and lawful operations.') }}
                            </p>
                        </section>

                        <section class="rounded-[24px] border border-slate-200/80 bg-white p-6 shadow-bucha sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary/10 text-sm font-bold text-bucha-primary">5</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('Data security') }}</h2>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ __('BuchaPro may apply reasonable administrative, organizational, and technical safeguards to reduce the risk of unauthorized access, misuse, alteration, or loss of information. No system can guarantee absolute security, so users should also protect their own credentials and access channels.') }}
                            </p>
                        </section>

                        <section class="rounded-[24px] border border-slate-200/80 bg-white p-6 shadow-bucha sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary/10 text-sm font-bold text-bucha-primary">6</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('Retention of information') }}</h2>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ __('Information may be retained for as long as reasonably necessary to operate the platform, maintain records, meet legal or regulatory obligations, resolve disputes, support audits, or enforce agreements.') }}
                            </p>
                        </section>

                        <section class="rounded-[24px] border border-slate-200/80 bg-white p-6 shadow-bucha sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary/10 text-sm font-bold text-bucha-primary">7</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('User choices and requests') }}</h2>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ __('Where appropriate and subject to legal, operational, or contractual limits, users may contact BuchaPro to ask questions about the information associated with their use of the platform or to request clarification about privacy handling.') }}
                            </p>
                        </section>

                        <section class="rounded-[24px] border border-slate-200/80 bg-white p-6 shadow-bucha sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary/10 text-sm font-bold text-bucha-primary">8</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('Updates to this policy') }}</h2>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ __('We may update this Privacy Policy from time to time to reflect changes in services, law, operational practice, or security requirements. Continued use of BuchaPro after an update indicates acceptance of the revised policy.') }}
                            </p>
                        </section>

                        <section class="rounded-[24px] border border-bucha-primary/15 bg-bucha-primary/5 p-6 sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-sm font-bold text-bucha-primary">9</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('Platform use and user responsibilities') }}</h2>
                            </div>
                            <ul class="mt-3 space-y-3 text-sm leading-relaxed text-slate-600">
                                <li>{{ __('BuchaPro must be used only for lawful, authorized, and legitimate operational or business purposes.') }}</li>
                                <li>{{ __('Users must provide accurate information, protect account credentials, and avoid misuse, disruption, or unauthorized access attempts.') }}</li>
                                <li>{{ __('Organizations and users remain responsible for the truthfulness, legality, and authorization of the records they submit.') }}</li>
                                <li>{{ __('BuchaPro branding, software, workflows, and related content remain the property of their owners or licensors unless otherwise stated.') }}</li>
                            </ul>
                        </section>

                        <section class="rounded-[24px] border border-bucha-primary/15 bg-white p-6 shadow-bucha sm:p-8">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bucha-primary/10 text-sm font-bold text-bucha-primary">10</span>
                                <h2 class="text-xl font-bold text-slate-900">{{ __('Contact') }}</h2>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ __('For privacy questions, support, or clarification, contact BuchaPro directly using the channels below.') }}
                            </p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="mailto:support@buchapro.com?subject={{ rawurlencode('Privacy policy enquiry') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-5 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-bucha-burgundy">
                                    {{ __('Email the team') }}
                                </a>
                                <a href="{{ route('contact-us') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                                    {{ __('Open Contact Page') }}
                                </a>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')
    @include('partials.pwa-install-prompt')
</body>
</html>
