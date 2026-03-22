<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Slaughter execution') }}
            </h2>
            <a href="{{ route('slaughter-executions.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                {{ __('Record execution') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total executions') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Completed') }}" :value="$kpis['completed']" color="green" />
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($executions->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No slaughter executions recorded yet.') }}</p>
                    <a href="{{ route('slaughter-executions.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                        {{ __('Record first execution') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($executions as $execution)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('slaughter-executions.show', $execution) }}" class="font-medium text-slate-900 hover:text-bucha-primary">
                                        {{ $execution->slaughter_time->format('d M Y H:i') }} — {{ $execution->slaughterPlan->facility->facility_name }}
                                    </a>
                                    <p class="text-sm text-slate-500">
                                        {{ __('Session') }} #{{ $execution->slaughter_plan_id }} · {{ $execution->actual_animals_slaughtered }} {{ __('animals slaughtered') }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ ucfirst(str_replace('_', ' ', $execution->status)) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('slaughter-executions.show', $execution) }}" class="text-sm text-bucha-primary hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('slaughter-executions.edit', $execution) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $executions->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
