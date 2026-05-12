@php
    $fmtPct = static fn (float $n): string => number_format($n, 2).'%';
@endphp

<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Farmer dashboard') }}</span>
    </x-slot>

    <div class="space-y-8 max-w-[1600px]">
        <section class="rounded-bucha border border-slate-200/80 bg-white px-4 py-4 sm:px-6 sm:py-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        {{ __('Welcome, :name', ['name' => $user->name]) }}
                    </h1>
                    <p class="mt-1 text-sm text-bucha-muted">{{ __('Livestock, health, and farm operations.') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('farmer.farms.index') }}" class="inline-flex items-center gap-2 rounded-bucha border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'building'])
                        {{ __('Manage farms') }}
                    </a>
                    <a href="{{ route('farmer.movement.hub') }}" class="inline-flex items-center gap-2 rounded-bucha bg-bucha-primary px-3 py-2 text-sm font-semibold text-white transition hover:opacity-95">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'clipboard-list'])
                        {{ __('Movement permits') }}
                    </a>
                </div>
            </div>
        </section>

        <div class="space-y-6">
            <section aria-labelledby="farmer-kpi-herd">
                <h2 id="farmer-kpi-herd" class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Herd & stock') }}</h2>
                <div class="grid grid-cols-2 gap-2 sm:gap-2.5 lg:grid-cols-3">
                    <x-kpi-card
                        stat
                        title="{{ __('Total livestock') }}"
                        :value="$totalLivestock"
                        :subtitle="__('Available: :count', ['count' => number_format($availableLivestock)])"
                        :href="route('farmer.livestock.index')"
                    >
                        <x-slot name="icon">@include('layouts.partials.sidebar-icon', ['icon' => 'box'])</x-slot>
                    </x-kpi-card>
                    <x-kpi-card
                        stat
                        title="{{ __('Healthy vs sick') }}"
                        :value="$fmtPct($healthyPercent).' / '.$fmtPct($sickPercent)"
                        :subtitle="__('Healthy: :healthy · Sick: :sick', ['healthy' => number_format($healthyLivestock), 'sick' => number_format($sickLivestock)])"
                        :href="route('farmer.health.hub')"
                    >
                        <x-slot name="icon">@include('layouts.partials.sidebar-icon', ['icon' => 'shield'])</x-slot>
                    </x-kpi-card>
                    <x-kpi-card
                        stat
                        title="{{ __('Growth rate') }}"
                        :value="$fmtPct($growthRatePercent)"
                        :subtitle="__('30 days: +:new / -:sold / -:dead', ['new' => number_format($newAnimals), 'sold' => number_format($soldAnimals), 'dead' => number_format($deadAnimals)])"
                        :href="route('farmer.livestock.index')"
                    >
                        <x-slot name="icon">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </x-slot>
                    </x-kpi-card>
                </div>
            </section>

            <section aria-labelledby="farmer-kpi-health">
                <h2 id="farmer-kpi-health" class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Health & compliance') }}</h2>
                <div class="grid grid-cols-2 gap-2 sm:gap-2.5 lg:grid-cols-3">
                    <x-kpi-card
                        stat
                        title="{{ __('Mortality rate') }}"
                        :value="$fmtPct($mortalityRatePercent)"
                        :subtitle="__('Recorded mortalities: :count', ['count' => number_format($mortalityCount)])"
                        :href="route('farmer.health.mortalities.index')"
                    >
                        <x-slot name="icon">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 5c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                        </x-slot>
                    </x-kpi-card>
                    <x-kpi-card
                        stat
                        title="{{ __('Compliance status') }}"
                        :value="$fmtPct($complianceStatusPercent)"
                        :subtitle="__(':certified / :total animals covered', ['certified' => number_format($certifiedAnimalCount), 'total' => number_format($totalAnimals)])"
                        :href="route('farmer.certificates.hub')"
                    >
                        <x-slot name="icon">@include('layouts.partials.sidebar-icon', ['icon' => 'certificate'])</x-slot>
                    </x-kpi-card>
                    <x-kpi-card
                        stat
                        title="{{ __('Passport coverage') }}"
                        :value="$fmtPct($passportCoveragePercent)"
                        :subtitle="__('Traceability passports on :count animals', ['count' => number_format($traceabilityAnimalCount)])"
                        :href="route('farmer.certificates.hub')"
                    >
                        <x-slot name="icon">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </x-slot>
                    </x-kpi-card>
                    <x-kpi-card
                        stat
                        title="{{ __('Average age / weight') }}"
                        :value="$weightedAge !== null ? number_format($weightedAge, 2).' '.__('years') : __('Not tracked')"
                        :subtitle="__('Weight: :weight', ['weight' => $weightedWeight !== null ? number_format($weightedWeight, 2).' '.__('kg') : __('Not tracked')])"
                        :href="route('farmer.livestock.index')"
                    >
                        <x-slot name="icon">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                        </x-slot>
                    </x-kpi-card>
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-bucha border border-slate-200/80 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-bucha-primary/10 text-bucha-primary" aria-hidden="true">
                            @include('layouts.partials.sidebar-icon', ['icon' => 'box'])
                        </span>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Total animals per species') }}</h2>
                    </div>
                </div>
                <div class="divide-y divide-slate-100 text-sm">
                    @forelse ($animalsPerSpecies as $speciesRow)
                        <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                            <span class="font-medium text-slate-900">{{ \App\Support\FarmerAnimalType::label((string) $speciesRow['type']) }}</span>
                            <span class="tabular-nums text-slate-700">
                                {{ number_format($speciesRow['total']) }}
                                <span class="text-xs text-slate-500">({{ $fmtPct((float) $speciesRow['share_percent']) }})</span>
                            </span>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-slate-500 sm:px-5">{{ __('No livestock recorded by species yet.') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-bucha-primary/10 text-bucha-primary" aria-hidden="true">
                            @include('layouts.partials.sidebar-icon', ['icon' => 'building'])
                        </span>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Stock distribution by farm') }}</h2>
                    </div>
                    <a href="{{ route('farmer.farms.index') }}" class="text-xs font-medium text-bucha-primary hover:underline">{{ __('All farms') }}</a>
                </div>
                <div class="divide-y divide-slate-100 text-sm">
                    @forelse ($stockDistributionByFarm as $farmRow)
                        <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <a href="{{ route('farmer.farms.show', $farmRow['farm']) }}" class="font-medium text-bucha-primary hover:underline">{{ $farmRow['farm']->name }}</a>
                                <p class="text-xs text-slate-500">{{ __('Share of total stock') }}: {{ $fmtPct((float) $farmRow['share_percent']) }}</p>
                            </div>
                            <div class="text-right tabular-nums text-slate-700">
                                <span class="font-medium text-slate-900">{{ number_format($farmRow['total']) }}</span>
                                <span class="text-slate-400">/</span>
                                <span>{{ number_format($farmRow['available']) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-slate-500 sm:px-5">
                            {{ __('No farm stock distribution available yet.') }}
                            <a href="{{ route('farmer.farms.create') }}" class="text-bucha-primary hover:underline">{{ __('Add a farm') }}</a>
                        </p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rounded-bucha border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-bucha-primary/10 text-bucha-primary" aria-hidden="true">
                    @include('layouts.partials.sidebar-icon', ['icon' => 'shield'])
                </span>
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Recent health records') }}</h2>
            </div>
            <div class="divide-y divide-slate-100 text-sm">
                @forelse ($recentHealth as $h)
                    <div class="grid grid-cols-[auto,auto,1fr] items-center gap-3 px-4 py-2.5 sm:px-5">
                        <span class="text-slate-600">{{ $h->record_date?->toDateString() }}</span>
                        <span class="capitalize text-slate-900">{{ $h->condition }}</span>
                        <span class="truncate text-slate-500">{{ $h->farm?->name }}</span>
                    </div>
                @empty
                    <p class="px-4 py-6 text-slate-500 sm:px-5">{{ __('No health records yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
