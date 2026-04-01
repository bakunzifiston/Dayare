<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Contact Us') }} - {{ config('app.name', 'BuchaPro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bucha-canvas text-slate-900">
    <header class="sticky top-0 z-20 bg-white/95 border-b border-slate-200/80">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <x-sidebar-brand href="{{ route('home') }}" theme="light" />
            <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 rounded-bucha border border-slate-200 hover:bg-slate-50 text-sm font-semibold">
                {{ __('Back to Home') }}
            </a>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden bg-gradient-to-br from-bucha-charcoal via-bucha-sidebar to-bucha-primary">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, #ffffff 2px, transparent 2px); background-size: 24px 24px;"></div>
            <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
                <p class="text-xs uppercase tracking-wider font-semibold text-white/80">{{ __('Get in touch') }}</p>
                <h1 class="mt-3 text-3xl sm:text-5xl font-bold text-white">{{ __('Contact Us') }}</h1>
                <p class="mt-4 text-white/90 max-w-2xl text-sm sm:text-base leading-relaxed">
                    {{ __('Have questions about onboarding, traceability, compliance, or partnerships? Our team is ready to help you move faster with confidence.') }}
                </p>
            </div>
        </section>

        <section class="py-10 sm:py-14">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-1 space-y-4">
                        <div class="rounded-[18px] border border-slate-200/80 bg-white p-5 shadow-bucha">
                            <p class="text-xs uppercase tracking-wider text-bucha-muted">{{ __('Email') }}</p>
                            <a href="mailto:support@buchapro.com" class="mt-2 block text-sm font-semibold text-bucha-primary hover:text-bucha-burgundy">support@buchapro.com</a>
                        </div>
                        <div class="rounded-[18px] border border-slate-200/80 bg-white p-5 shadow-bucha">
                            <p class="text-xs uppercase tracking-wider text-bucha-muted">{{ __('Contact') }}</p>
                            <a href="tel:+250793902451" class="mt-2 block text-sm font-semibold text-slate-900 hover:text-bucha-primary">+250 793 902 451</a>
                            <a href="tel:+250781161487" class="mt-1 block text-sm font-semibold text-slate-900 hover:text-bucha-primary">+250 781 161 487</a>
                        </div>
                        <div class="rounded-[18px] border border-slate-200/80 bg-white p-5 shadow-bucha">
                            <p class="text-xs uppercase tracking-wider text-bucha-muted">{{ __('Address') }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">44 KG 548 St, Kigali, Rwanda</p>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Mon - Fri, 8:00 AM - 6:00 PM') }}</p>
                        </div>
                    </div>

                    <div class="lg:col-span-2 rounded-[18px] border border-slate-200/80 bg-white p-6 sm:p-8 shadow-bucha">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900">{{ __('Send us a message') }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ __('Fill in your details and we will get back to you shortly.') }}</p>

                        <form class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4" action="#" method="post">
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700">{{ __('Full name') }}</label>
                                <input id="name" type="text" class="mt-1 w-full rounded-bucha border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary" placeholder="{{ __('Your full name') }}">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                                <input id="email" type="email" class="mt-1 w-full rounded-bucha border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary" placeholder="{{ __('name@example.com') }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="subject" class="block text-sm font-medium text-slate-700">{{ __('Subject') }}</label>
                                <input id="subject" type="text" class="mt-1 w-full rounded-bucha border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary" placeholder="{{ __('How can we help?') }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="message" class="block text-sm font-medium text-slate-700">{{ __('Message') }}</label>
                                <textarea id="message" rows="5" class="mt-1 w-full rounded-bucha border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary" placeholder="{{ __('Write your message...') }}"></textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <button type="button" class="inline-flex items-center px-5 py-3 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-sm font-semibold tracking-wide transition-colors">
                                    {{ __('Send message') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('layouts.footer')
</body>
</html>
