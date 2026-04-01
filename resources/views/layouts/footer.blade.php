<footer class="mt-auto border-t border-slate-200/80 bg-bucha-charcoal text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm font-bold text-white">{{ config('app.name', 'BuchaPro') }}</p>
                <p class="mt-2 text-xs sm:text-sm text-white/80">
                    {{ __('Track. Verify. Trust. End-to-end meat traceability and compliance.') }}
                </p>
            </div>

            <div>
                <p class="text-sm font-semibold text-white">{{ __('Quick menu') }}</p>
                <div class="mt-3 grid grid-cols-2 gap-y-2 text-xs sm:text-sm">
                    <a href="{{ route('home') }}" class="text-white/80 hover:text-white">{{ __('Home') }}</a>
                    <a href="{{ route('home') }}#ecosystem" class="text-white/80 hover:text-white">{{ __('Ecosystem') }}</a>
                    <a href="{{ route('home') }}#how-it-works" class="text-white/80 hover:text-white">{{ __('How it works') }}</a>
                    <a href="{{ route('home') }}#products" class="text-white/80 hover:text-white">{{ __('Products') }}</a>
                    <a href="{{ route('contact-us') }}" class="text-white/80 hover:text-white">{{ __('Contact Us') }}</a>
                    <a href="{{ route('register') }}" class="text-white/80 hover:text-white">{{ __('Become a Partner') }}</a>
                </div>
            </div>

            <div>
                <p class="text-sm font-semibold text-white">{{ __('Connect with us') }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <a href="#" aria-label="Facebook" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/30 text-white/80 hover:text-white hover:border-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.6-1.6h1.7V4.8c-.8-.1-1.5-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4V11H8v3h2.6v8h2.9z"/></svg>
                    </a>
                    <a href="#" aria-label="Instagram" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/30 text-white/80 hover:text-white hover:border-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7.8 2h8.4A5.8 5.8 0 0 1 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8A5.8 5.8 0 0 1 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2zm0 1.9A3.9 3.9 0 0 0 3.9 7.8v8.4a3.9 3.9 0 0 0 3.9 3.9h8.4a3.9 3.9 0 0 0 3.9-3.9V7.8a3.9 3.9 0 0 0-3.9-3.9H7.8zm8.9 1.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 1.9a3.1 3.1 0 1 0 0 6.2 3.1 3.1 0 0 0 0-6.2z"/></svg>
                    </a>
                    <a href="#" aria-label="LinkedIn" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/30 text-white/80 hover:text-white hover:border-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.9 8.3A1.9 1.9 0 1 1 6.9 4.5 1.9 1.9 0 0 1 6.9 8.3zM5.2 9.8h3.3V20H5.2V9.8zM10.4 9.8h3.2v1.4h.1c.4-.8 1.5-1.7 3.2-1.7 3.4 0 4 2.2 4 5v5.5h-3.3v-4.9c0-1.2 0-2.7-1.7-2.7s-1.9 1.3-1.9 2.6V20h-3.3V9.8z"/></svg>
                    </a>
                    <a href="#" aria-label="X" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/30 text-white/80 hover:text-white hover:border-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 3H22l-6.8 7.8L23 21h-6.2l-4.8-6.3L6.5 21H3.4l7.3-8.4L3.3 3h6.3l4.3 5.7L18.9 3zm-1.1 16h1.7L8.8 4.9H7.1L17.8 19z"/></svg>
                    </a>
                </div>

                <p class="mt-4 text-xs sm:text-sm text-white/80">
                    <span class="font-semibold text-white">{{ __('Contact') }}:</span>
                    <a href="tel:+250793902451" class="hover:text-white">+250 793 902 451</a>,
                    <a href="tel:+250781161487" class="hover:text-white">+250 781 161 487</a>
                    <span class="mx-2 hidden sm:inline">|</span>
                    <span class="block sm:inline">44 KG 548 St, Kigali, Rwanda</span>
                </p>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-white/15">
            <p class="text-xs sm:text-sm text-white/70">
                &copy; {{ date('Y') }} {{ config('app.name', 'BuchaPro') }}. {{ __('All rights reserved.') }}
            </p>
        </div>
    </div>
</footer>
