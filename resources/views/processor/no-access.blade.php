<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            {{ __('Workspace access') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-3xl py-10">
        <section class="rounded-bucha border border-slate-200 bg-white p-8 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.7 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/>
                </svg>
            </div>
            <h1 class="mt-4 text-xl font-semibold text-slate-900">{{ __('No workspace access assigned') }}</h1>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-slate-600">
                {{ __('Your account is active, but your current role does not have access to any processor modules. Contact your business owner to update your role access.') }}
            </p>
            <a href="{{ route('profile.edit') }}" class="mt-6 inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                {{ __('Open profile') }}
            </a>
        </section>
    </div>
</x-app-layout>
