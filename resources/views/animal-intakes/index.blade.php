<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('animal-intakes.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Animal intake') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('All intakes') }}
                </h2>
            </div>
            <a href="{{ route('animal-intakes.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Record intake') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total intakes') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Received') }}" :value="$kpis['received']" color="slate" />
                <x-kpi-card inline title="{{ __('Approved') }}" :value="$kpis['approved']" color="green" />
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($intakes->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No animal intakes yet.') }}</p>
                    <p class="text-sm mb-4">{{ __('Record where animals come from before scheduling slaughter. Intake must be recorded before creating a slaughter plan.') }}</p>
                    <a href="{{ route('animal-intakes.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Record first intake') }}</a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($intakes as $i)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('animal-intakes.show', $i) }}" class="font-medium text-slate-900 hover:text-bucha-primary">
                                        {{ $i->intake_date->format('d M Y') }} — {{ $i->facility->facility_name ?? '' }}
                                    </a>
                                    <p class="text-sm text-slate-500">
                                        {{ $i->supplier_firstname }} {{ $i->supplier_lastname }} · {{ $i->farm_name ?? '—' }} · {{ __($i->species) }} · {{ $i->number_of_animals }} {{ __('animals') }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ ucfirst($i->status) }} · {{ $i->animal_health_certificate_number ?: __('No health cert') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('animal-intakes.show', $i) }}" class="text-sm text-bucha-primary hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('animal-intakes.edit', $i) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $intakes->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
