<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Inspectors') }}
            </h2>
            <a href="{{ route('inspectors.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Register Inspector') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="text-sm text-slate-600 mb-6 max-w-3xl">
                {{ __('Register inspectors against slaughterhouses and other sites. They can be assigned to slaughter sessions, ante-mortem checks, batches, and certificates.') }}
            </p>

            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total inspectors') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Active') }}" :value="$kpis['active']" color="green" />
                <x-kpi-card inline title="{{ __('Expired status') }}" :value="$kpis['expired']" color="amber" />
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @include('inspectors.partials.inspector-list', ['inspectors' => $inspectors])
        </div>
    </div>
</x-app-layout>
