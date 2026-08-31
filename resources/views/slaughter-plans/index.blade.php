<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Slaughter planning') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Actions') }}">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold text-slate-900">{{ __('All plans') }}</p>
                <a href="{{ route('slaughter-plans.hub') }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                <a href="{{ route('slaughter-plans.create') }}" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Schedule slaughter') }}</a>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Plans summary') }}">
            <x-kpi-card stat compact color="slate" :title="__('Total plans')" :value="$kpis['total']" glyph="clipboard" />
            <x-kpi-card stat compact color="amber" :title="__('Planned')" :value="$kpis['planned']" glyph="calendar" />
            <x-kpi-card stat compact color="bucha-success" :title="__('Approved')" :value="$kpis['approved']" glyph="check" />
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Plans') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $plans->total(), ['count' => number_format($plans->total())]) }}</p>
            </div>
            @if ($plans->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ __('No slaughter plans yet') }}</p>
                    <a href="{{ route('slaughter-plans.create') }}" class="mt-4 inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Schedule slaughter') }}</a>
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($plans as $plan)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50/70">
                            <div>
                                <a href="{{ route('slaughter-plans.show', $plan) }}" class="text-sm font-medium text-slate-900 hover:text-bucha-primary">
                                    {{ $plan->slaughterDateDisplay() }} — {{ $plan->facility->facility_name }}
                                </a>
                                <p class="text-xs text-slate-500">
                                    {{ $plan->species }} · {{ $plan->number_of_animals_scheduled }} {{ __('animals') }} · {{ $plan->inspector->full_name }} · {{ ucfirst($plan->status) }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <a href="{{ route('slaughter-plans.show', $plan) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('View') }}</a>
                                <a href="{{ route('slaughter-plans.edit', $plan) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-slate-100 px-4 py-3">{{ $plans->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
