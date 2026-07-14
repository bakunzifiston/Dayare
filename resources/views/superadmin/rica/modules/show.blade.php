<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <a href="{{ route('rica.dashboard') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy shrink-0">{{ __('← RICA') }}</a>
            <span class="hidden sm:inline text-slate-300" aria-hidden="true">·</span>
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                <span class="[&>svg]:h-4 [&>svg]:w-4">
                    @include('layouts.partials.sidebar-icon', ['icon' => $icon ?? 'shield'])
                </span>
            </span>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight shrink-0">{{ $title }}</h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
            <p class="text-sm text-slate-600 leading-relaxed">{{ $description }}</p>

            @if (! empty($highlights))
                <ul class="space-y-2">
                    @foreach ($highlights as $highlight)
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-bucha-primary" aria-hidden="true"></span>
                            <span>{{ $highlight }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                {{ __('This module is being prepared for the RICA oversight workspace. Core slaughter, inspection, and reporting data is already available under Traceability and Reports.') }}
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <a href="{{ route('rica.dashboard') }}"
                   class="inline-flex items-center px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('Traceability') }}
                </a>
                <a href="{{ route('rica.reports') }}"
                   class="inline-flex items-center px-3 py-2 rounded-md border border-bucha-primary text-xs font-semibold text-bucha-primary hover:bg-bucha-primary/5">
                    {{ __('Reports') }}
                </a>
                <a href="{{ route('rica.monthly-reports.index') }}"
                   class="inline-flex items-center px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('Monthly inspection reports') }}
                </a>
            </div>
        </section>
    </div>
</x-app-layout>
