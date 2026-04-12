<x-app-layout>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-600 mb-4">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-semibold text-slate-800 mb-2">{{ __('Access denied') }}</h1>
                <p class="text-slate-600 mb-6">
                    {{ $message ?? __('You do not have permission to access this section.') }}
                </p>
                <a href="{{ Auth::check() ? route(Auth::user()->defaultDashboardRouteName()) : route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-bucha font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Go to Dashboard') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
