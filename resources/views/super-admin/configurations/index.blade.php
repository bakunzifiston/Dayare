<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Global configuration') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">{{ __('Reference data') }}</h3>
                <p class="mt-1 text-sm text-slate-600">
                    {{ __('Species and units are managed globally here and consumed as read-only selections by all tenant workspaces.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-slate-900">{{ __('Species') }}</h4>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">
                            {{ $activeSpeciesCount }}/{{ $speciesCount }} {{ __('active') }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Manage available species for dropdowns across tenants.') }}</p>
                    <a href="{{ route('super-admin.species.index') }}" class="mt-4 inline-flex items-center rounded-md bg-bucha-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Open species') }}
                    </a>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-slate-900">{{ __('Units') }}</h4>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">
                            {{ $activeUnitCount }}/{{ $unitCount }} {{ __('active') }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Manage available units for dropdowns across tenants.') }}</p>
                    <a href="{{ route('super-admin.units.index') }}" class="mt-4 inline-flex items-center rounded-md bg-bucha-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Open units') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
