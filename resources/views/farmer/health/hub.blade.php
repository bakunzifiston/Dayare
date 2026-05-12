<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800">{{ __('Health management') }}</h2>
    </x-slot>

    <div class="max-w-7xl space-y-6">
        @include('farmer.health.partials.nav')

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-kpi-card stat :title="__('Total vaccinations')" :value="$metrics['total_vaccinations']" color="blue" />
            <x-kpi-card stat :title="__('Upcoming vaccinations')" :value="$metrics['upcoming_vaccinations']" color="amber" :href="route('farmer.health.vaccinations.index')" />
            <x-kpi-card stat :title="__('Sick animals')" :value="$metrics['sick_animals']" color="bucha" />
            <x-kpi-card stat :title="__('Under treatment')" :value="$metrics['under_treatment']" color="amber" :href="route('farmer.health.treatments.index')" />
            <x-kpi-card stat :title="__('Mortality count')" :value="$metrics['mortality_count']" color="slate" :href="route('farmer.health.mortalities.index')" />
            <x-kpi-card stat :title="__('Recovery rate')" :value="$metrics['recovery_rate'].'%'" color="bucha-success" />
            <x-kpi-card stat :title="__('Disease outbreaks')" :value="$metrics['disease_outbreaks']" color="bucha" :href="route('farmer.health.diseases.index')" />
            <x-kpi-card stat :title="__('Overdue vaccinations')" :value="$metrics['overdue_vaccinations']" color="bucha" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-bucha border border-amber-200 bg-amber-50 p-4">
                <h3 class="text-sm font-semibold text-amber-900">{{ __('Upcoming vaccination alerts') }}</h3>
                @forelse ($upcomingVaccinations as $record)
                    <div class="mt-3 rounded-lg border border-amber-100 bg-white px-3 py-2 text-sm">
                        <p class="font-medium text-slate-900">{{ $record->vaccine_name }}</p>
                        <p class="text-slate-600">{{ $record->animal?->animal_code }} · {{ $record->next_due_date?->toDateString() }}</p>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-amber-800">{{ __('No vaccinations due in the next 14 days.') }}</p>
                @endforelse
            </section>

            <section class="rounded-bucha border border-red-200 bg-red-50 p-4">
                <h3 class="text-sm font-semibold text-red-900">{{ __('Overdue vaccination alerts') }}</h3>
                @forelse ($overdueVaccinations as $record)
                    <div class="mt-3 rounded-lg border border-red-100 bg-white px-3 py-2 text-sm">
                        <p class="font-medium text-slate-900">{{ $record->vaccine_name }}</p>
                        <p class="text-slate-600">{{ $record->animal?->animal_code }} · {{ $record->next_due_date?->toDateString() }}</p>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-red-800">{{ __('No overdue vaccinations.') }}</p>
                @endforelse
            </section>
        </div>

        <section class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Treatment follow-up reminders') }}</h3>
            @forelse ($followUpTreatments as $record)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-100 px-3 py-2 text-sm">
                    <div>
                        <p class="font-medium text-slate-900">{{ $record->disease_name ?: $record->medicine_name }}</p>
                        <p class="text-slate-600">{{ $record->animal?->animal_code }} · {{ $record->follow_up_date?->toDateString() }}</p>
                    </div>
                    <a href="{{ route('farmer.health.treatments.show', $record) }}" class="text-bucha-primary hover:underline">{{ __('Review') }}</a>
                </div>
            @empty
                <p class="mt-2 text-sm text-slate-500">{{ __('No treatment follow-ups due in the next 14 days.') }}</p>
            @endforelse
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach (['vaccination_trend' => __('Vaccination trends'), 'disease_frequency' => __('Disease frequency'), 'mortality_trend' => __('Mortality trends'), 'treatment_success' => __('Treatment success rate')] as $chartId => $title)
                <section class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                    <div class="mt-4 h-56">
                        <canvas id="chart-{{ str_replace('_', '-', $chartId) }}" aria-label="{{ $title }}"></canvas>
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    @vite('resources/js/dashboard-charts.js')
    <script>
        window.dashboardCharts = @json($charts);
    </script>
</x-app-layout>
