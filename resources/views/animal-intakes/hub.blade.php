@php
    use App\Models\AnimalIntake;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Animal intake') }}
            </h2>
            <a href="{{ route('animal-intakes.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Record intake') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                @if (session('status'))
                    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif
                @if (session('warning'))
                    <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">{{ session('warning') }}</div>
                @endif

                <form method="get" action="{{ route('animal-intakes.hub') }}" class="hub-period-filter">
                    @if (request()->filled('reference'))
                        <input type="hidden" name="reference" value="{{ request('reference') }}">
                    @endif
                    <div class="hub-period-filter__bar">
                        <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Intake period') }}">
                            @foreach (['all' => __('All'), 'day' => __('Daily'), 'month' => __('Monthly'), 'year' => __('Yearly')] as $periodKey => $periodLabel)
                                <label class="hub-period-filter__toggle">
                                    <input type="radio" name="period" value="{{ $periodKey }}" @checked($filters['period'] === $periodKey)>
                                    <span>{{ $periodLabel }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="hub-period-filter__range">
                            <label for="filter_date_from" class="hub-period-filter__range-label">{{ __('From') }}</label>
                            <input id="filter_date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="hub-period-filter__input" aria-label="{{ __('Date from') }}">
                            <span class="hub-period-filter__sep" aria-hidden="true">–</span>
                            <label for="filter_date_to" class="hub-period-filter__range-label">{{ __('To') }}</label>
                            <input id="filter_date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="hub-period-filter__input" aria-label="{{ __('Date to') }}">
                        </div>

                        <div class="hub-period-filter__actions">
                            <button type="submit" class="hub-period-filter__apply">{{ __('Apply') }}</button>
                            @if ($filters['is_filtered'] || request()->filled('reference'))
                                <a href="{{ route('animal-intakes.hub') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                            @endif
                        </div>
                    </div>
                    <p class="hub-period-filter__hint">
                        {{ $filters['range_label'] }}
                        @if (request()->filled('reference'))
                            · {{ __('Reference') }}: {{ request('reference') }}
                        @endif
                    </p>
                </form>

                <div class="profile-kpi-grid">
                    <x-entity.kpi-stat :label="__('Animals on site')" :value="number_format($hubStats['heads_available'])" accent>
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="$hubStats['intakes_label']" :value="number_format($hubStats['intakes_in_period'])" accent>
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Cattle')" :value="number_format($hubStats['cattle_count'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Goat')" :value="number_format($hubStats['goat_count'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Sheep')" :value="number_format($hubStats['sheep_count'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                </div>

                @if ($intakes->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">
                            {{ $filters['is_filtered'] || request()->filled('reference') ? __('No intakes match this filter.') : __('No intakes recorded yet.') }}
                        </p>
                        <a href="{{ route('animal-intakes.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Record first intake') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($intakes as $intake)
                            @php
                                $health = $intake->health_summary;
                                $expiry = $intake->health_certificate_expiry_date;
                                $sourceName = $intake->source_type === AnimalIntake::SOURCE_TYPE_CLIENT
                                    ? ($intake->client?->name ?? $intake->clientSourceDisplayName())
                                    : (trim(($intake->supplier?->first_name ?? '').' '.($intake->supplier?->last_name ?? ''))
                                        ?: trim(($intake->supplier_firstname ?? '').' '.($intake->supplier_lastname ?? ''))
                                        ?: '—');
                                $statusTone = $intake->isDraft()
                                    ? 'warning'
                                    : match ($intake->status) {
                                        AnimalIntake::STATUS_APPROVED => 'active',
                                        AnimalIntake::STATUS_REJECTED => 'danger',
                                        default => 'muted',
                                    };
                                $statusLabel = $intake->isDraft() ? __('Draft') : ucfirst($intake->status);
                                $initial = strtoupper(substr($intake->reference ?? 'I', 0, 1));
                                $healthParts = collect([
                                    $health['healthy'] > 0 ? $health['healthy'].' '.__('healthy') : null,
                                    $health['under_observation'] > 0 ? $health['under_observation'].' '.__('observation') : null,
                                    $health['rejected'] > 0 ? $health['rejected'].' '.__('rejected') : null,
                                ])->filter()->implode(', ');
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>
                                    <a href="{{ route('animal-intakes.show', $intake) }}">{{ $intake->reference ?? __('Intake #:id', ['id' => $intake->id]) }}</a>
                                </x-slot:title>
                                <x-slot:subtitle>{{ $intake->facility->facility_name ?? '—' }}</x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$statusTone" :label="$statusLabel" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Date & time')">{{ $intake->intakeDatetimeLabel() }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Source')">{{ $sourceName }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Animals')">{{ number_format($intake->number_of_animals) }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Species')">{{ $intake->species_mix_label ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Health')">{{ $healthParts !== '' ? $healthParts : '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Cert expiry')">
                                    @if ($expiry)
                                        <span class="{{ $expiry->isPast() ? 'text-red-600 font-medium' : ($expiry->lte(today()->addDays(30)) ? 'text-amber-700 font-medium' : '') }}">
                                            {{ $expiry->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-amber-700">{{ __('Missing') }}</span>
                                    @endif
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Available')">
                                    {{ number_format($intake->remainingAnimalsAvailable()) }}
                                </x-entity.profile-row>

                                <x-slot:highlights>
                                    <x-entity.profile-highlight
                                        :value="number_format($intake->number_of_animals)"
                                        :label="__('Animals')"
                                    />
                                    <x-entity.profile-highlight
                                        :value="'RWF '.number_format($intake->total_price, 0)"
                                        :label="__('Total value')"
                                    />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('animal-intakes.show', $intake)">{{ __('View') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('animal-intakes.edit', $intake)">{{ __('Edit') }}</x-entity.text-action>
                                    @if ($intake->isPlannableForSlaughter() && $intake->remainingAnimalsAvailable() > 0)
                                        <x-entity.text-action :href="route('slaughter-plans.create').'?animal_intake_id='.$intake->id.'&facility_id='.$intake->facility_id">{{ __('Schedule slaughter') }}</x-entity.text-action>
                                    @endif
                                    <x-entity.text-action-delete
                                        :action="route('animal-intakes.destroy', $intake)"
                                        :confirm="__('Are you sure you want to delete this animal intake? This cannot be undone.')"
                                    >{{ __('Delete') }}</x-entity.text-action-delete>
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $intakes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
