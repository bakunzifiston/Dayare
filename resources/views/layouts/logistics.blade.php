<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Logistics Workspace') }}</span>
    </x-slot>

    <div
        x-data="logisticsWorkspaceShell()"
        x-init="init()"
        class="relative"
    >
        <section class="min-w-0 rounded-bucha border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ $pageTitle ?? __('Logistics') }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $pageSubtitle ?? __('Manage logistics operations.') }}</p>
                </div>
                @if (isset($actions) && trim((string) $actions) !== '')
                    <div class="shrink-0">
                        {{ $actions }}
                    </div>
                @endif
            </header>

            @if (session('status'))
                <div class="mx-5 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mx-5 mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div
                x-show="contentVisible"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="p-5"
            >
                {{ $slot }}
            </div>
        </section>

        <div
            x-show="isNavigating"
            x-transition.opacity
            class="pointer-events-none fixed inset-0 z-20 bg-white/70 backdrop-blur-[1px]"
        >
            <div class="mx-auto mt-28 w-full max-w-3xl space-y-3 px-6">
                <div class="h-5 w-40 animate-pulse rounded bg-slate-200"></div>
                <div class="h-10 w-full animate-pulse rounded bg-slate-200"></div>
                <div class="h-44 w-full animate-pulse rounded bg-slate-200"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function logisticsWorkspaceShell() {
                return {
                    isNavigating: false,
                    contentVisible: false,
                    scrollKey: 'logistics-scroll:' + window.location.pathname,
                    init() {
                        const saved = sessionStorage.getItem(this.scrollKey);
                        if (saved !== null) {
                            window.scrollTo(0, Number(saved));
                        }
                        this.contentVisible = true;
                        document.querySelectorAll('[data-logistics-nav]').forEach((link) => {
                            link.addEventListener('click', () => {
                                this.isNavigating = true;
                            });
                        });
                        window.addEventListener('beforeunload', () => {
                            sessionStorage.setItem(this.scrollKey, String(window.scrollY));
                        });
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
