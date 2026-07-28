<footer class="w-full shrink-0 border-t border-slate-200/80 text-white" style="background-color: var(--bucha-sidebar);">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1.25fr_0.9fr_1fr] lg:items-start">
            <div>
                <p class="text-sm font-bold text-white">{{ config('app.name', 'BuchaPro') }}</p>
                <p class="mt-3 max-w-md text-sm leading-relaxed text-white/75">
                    {{ __('Professional infrastructure for traceability, compliance workflows, certificate readiness, and cold-chain visibility across the meat value chain.') }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/55">{{ __('Explore') }}</p>
                <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    <a href="{{ route('home') }}#mobile-platform" class="text-white/75 transition-colors hover:text-white">{{ __('Mobile') }}</a>
                    <a href="{{ route('home') }}#what-is-buchapro" class="text-white/75 transition-colors hover:text-white">{{ __('About') }}</a>
                    <a href="{{ route('home') }}#how-it-works" class="text-white/75 transition-colors hover:text-white">{{ __('How it works') }}</a>
                    <a href="{{ route('home') }}#ecosystem" class="text-white/75 transition-colors hover:text-white">{{ __('Ecosystem') }}</a>
                    <a href="{{ route('home') }}#platform-features" class="text-white/75 transition-colors hover:text-white">{{ __('Features') }}</a>
                    <a href="{{ route('contact-us') }}" class="text-white/75 transition-colors hover:text-white">{{ __('Contact') }}</a>
                    <a href="{{ route('privacy-policy') }}" class="text-white/75 transition-colors hover:text-white">{{ __('Privacy Policy') }}</a>
                    @if (config('features.shop'))
                        <a href="{{ route('home') }}#products" class="text-white/75 transition-colors hover:text-white">{{ __('Products') }}</a>
                    @endif
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/55">{{ __('Contact') }}</p>
                <div class="mt-4 space-y-3 text-sm text-white/75">
                    <div>
                        <a href="mailto:support@buchapro.com" class="transition-colors hover:text-white">support@buchapro.com</a>
                    </div>
                    <div>
                        <a href="tel:+250783092757" class="transition-colors hover:text-white">+250 783 092 757</a>
                    </div>
                    <div>
                        <p>44 KG 548 St, Kigali, Rwanda</p>
                        <p class="text-white/55">{{ __('Mon - Fri, 8:00 AM - 6:00 PM') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-3 border-t border-white/8 pt-4 text-xs sm:flex-row sm:items-center sm:justify-between sm:text-sm">
            <p class="text-white/60">
                &copy; {{ date('Y') }} {{ config('app.name', 'BuchaPro') }}. {{ __('All rights reserved.') }}
            </p>
            <div class="flex flex-wrap gap-3 text-white/60">
                <a href="{{ route('privacy-policy') }}" class="transition-colors hover:text-white">{{ __('Privacy Policy') }}</a>
                <a href="{{ route('contact-us') }}" class="transition-colors hover:text-white">{{ __('Request a Consultation') }}</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="transition-colors hover:text-white">{{ __('Become a Partner') }}</a>
                @endif
            </div>
        </div>
    </div>
</footer>
