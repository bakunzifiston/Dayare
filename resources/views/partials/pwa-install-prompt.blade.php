@if (config('pwa.enabled'))
    <div
        id="pwa-install-banner"
        class="hidden fixed inset-x-0 bottom-0 z-50 p-4 sm:p-5 pointer-events-none"
        role="region"
        aria-label="{{ __('Install app') }}"
        hidden
    >
        <div class="pointer-events-auto mx-auto flex max-w-3xl flex-col gap-4 rounded-bucha border border-slate-200/90 bg-white p-4 shadow-bucha-md sm:flex-row sm:items-center sm:gap-5 sm:p-5">
            <div class="flex min-w-0 flex-1 items-start gap-3 sm:items-center sm:gap-4">
                <img
                    src="{{ asset('pwa-icon-192.png') }}"
                    alt=""
                    width="48"
                    height="48"
                    class="h-12 w-12 shrink-0 rounded-xl border border-slate-200/80"
                >
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ __('Install :app', ['app' => config('pwa.short_name')]) }}
                    </p>
                    <p data-pwa-install-default class="mt-1 text-sm text-slate-600">
                        {{ __('Add BuchaPro to your home screen for quick access and offline browsing on public pages.') }}
                    </p>
                    <p data-pwa-install-ios class="mt-1 hidden text-sm text-slate-600" hidden>
                        {{ __('Tap the Share button, then choose “Add to Home Screen”.') }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 sm:flex-col sm:items-stretch">
                <button
                    type="button"
                    data-pwa-install-action
                    class="inline-flex flex-1 items-center justify-center rounded-bucha bg-bucha-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-bucha-burgundy sm:flex-none"
                >
                    {{ __('Install') }}
                </button>
                <button
                    type="button"
                    data-pwa-install-dismiss
                    class="inline-flex flex-1 items-center justify-center rounded-bucha border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 sm:flex-none"
                >
                    {{ __('Not now') }}
                </button>
            </div>
        </div>
    </div>
@endif
