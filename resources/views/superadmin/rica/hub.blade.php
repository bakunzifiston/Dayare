<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-100" aria-hidden="true">
                    <span class="[&>svg]:h-5 [&>svg]:w-5">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'shield-check'])
                    </span>
                </span>
                <div class="min-w-0">
                    <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('RICA oversight') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ __('Regulatory oversight of all registered slaughterhouses.') }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <a href="{{ route('rica.slaughterhouses.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <span class="[&>svg]:h-4 [&>svg]:w-4 text-slate-500" aria-hidden="true">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'building'])
                    </span>
                    {{ __('All slaughterhouses') }}
                </a>
                <a href="{{ route('rica.reports') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                    <span class="[&>svg]:h-4 [&>svg]:w-4" aria-hidden="true">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'chart'])
                    </span>
                    {{ __('Reports') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <x-kpi-card
                stat
                glyph="building"
                color="slate"
                :title="__('Registered slaughterhouses')"
                :value="$hubStats['total_slaughterhouses']"
                :href="route('rica.slaughterhouses.index')"
            />
            <x-kpi-card
                stat
                glyph="users"
                color="blue"
                :title="__('Licensed operators')"
                :value="$hubStats['total_operators']"
            />
            <x-kpi-card
                stat
                glyph="intake"
                color="blue"
                :title="__('Animals slaughtered this month')"
                :value="$hubStats['animals_slaughtered_month']"
            />
            <x-kpi-card
                stat
                glyph="weight"
                color="slate"
                :title="__('Total meat yield this month')"
                :value="number_format($hubStats['meat_kg_month'], 2).' kg'"
            />
            <x-kpi-card
                stat
                glyph="alert"
                :color="$hubStats['condemned_month'] > 0 ? 'bucha' : 'slate'"
                :title="__('Animals condemned this month')"
                :value="$hubStats['condemned_month']"
            />
            <x-kpi-card
                stat
                glyph="certificate"
                color="green"
                :title="__('Certificates issued this month')"
                :value="$hubStats['certificates_month']"
            />
        </section>

        <section class="rounded-xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200" aria-hidden="true">
                        <span class="[&>svg]:h-4 [&>svg]:w-4">
                            @include('layouts.partials.sidebar-icon', ['icon' => 'building'])
                        </span>
                    </span>
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Registered slaughterhouses') }}</h3>
                </div>
                @if ($hubStats['total_slaughterhouses'] > 6)
                    <a href="{{ route('rica.slaughterhouses.index') }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy shrink-0">
                        {{ __('View all :count →', ['count' => number_format($hubStats['total_slaughterhouses'])]) }}
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
                @forelse ($slaughterhouses as $facility)
                    <a href="{{ route('rica.slaughterhouses.show', $facility) }}"
                       class="group flex h-full flex-col rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:border-bucha-primary/25 hover:bg-slate-50/50 hover:shadow-md">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100" aria-hidden="true">
                                <span class="[&>svg]:h-4 [&>svg]:w-4">
                                    @include('layouts.partials.sidebar-icon', ['icon' => 'building'])
                                </span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900 group-hover:text-bucha-primary truncate">
                                    {{ $facility->facility_name }}
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5 truncate">
                                    {{ $facility->business->business_name ?? '—' }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                            <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                <span class="[&>svg]:h-3.5 [&>svg]:w-3.5" aria-hidden="true">
                                    @include('layouts.partials.sidebar-icon', ['icon' => 'clipboard-list'])
                                </span>
                                {{ trans_choice(':count plan|:count plans', $facility->slaughter_plans_count, ['count' => $facility->slaughter_plans_count]) }}
                            </span>
                            <span class="text-xs font-medium text-bucha-primary opacity-0 transition-opacity group-hover:opacity-100">
                                {{ __('View records →') }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center py-12 text-center">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3" aria-hidden="true">
                            <span class="[&>svg]:h-6 [&>svg]:w-6">
                                @include('layouts.partials.sidebar-icon', ['icon' => 'building'])
                            </span>
                        </span>
                        <p class="text-sm text-slate-500">{{ __('No slaughterhouses registered yet.') }}</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200" aria-hidden="true">
                    <span class="[&>svg]:h-4 [&>svg]:w-4">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'play'])
                    </span>
                </span>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent slaughter activity') }}</h3>
            </div>

            @forelse ($recentExecutions as $execution)
                @php
                    $facility = $execution->slaughterPlan?->facility;
                    $operator = $facility?->business;
                    [$statusIcon, $statusColor] = match ($execution->status) {
                        \App\Models\SlaughterExecution::STATUS_COMPLETED => ['check', 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
                        \App\Models\SlaughterExecution::STATUS_IN_PROGRESS => ['play', 'bg-blue-50 text-blue-700 ring-blue-100'],
                        default => ['clock', 'bg-slate-100 text-slate-500 ring-slate-200'],
                    };
                @endphp
                <div class="flex items-center gap-4 px-4 py-3 border-b border-slate-100 last:border-b-0 hover:bg-slate-50/70 transition-colors">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full ring-1 ring-inset {{ $statusColor }}" aria-hidden="true">
                        <span class="[&>svg]:h-4 [&>svg]:w-4">
                            @include('layouts.partials.sidebar-icon', ['icon' => $statusIcon])
                        </span>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">
                            {{ $facility?->facility_name ?? '—' }}
                            <span class="font-normal text-slate-400">· {{ $operator?->business_name ?? '—' }}</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $execution->slaughter_time?->format('d M Y H:i') ?? '—' }}
                            · {{ number_format((int) $execution->actual_animals_slaughtered) }} {{ __('animals') }}
                        </p>
                    </div>
                    @if ($facility)
                        <a href="{{ route('rica.slaughterhouses.show', $facility) }}"
                           class="inline-flex items-center gap-1 text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy shrink-0">
                            {{ __('View') }}
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3" aria-hidden="true">
                        <span class="[&>svg]:h-6 [&>svg]:w-6">
                            @include('layouts.partials.sidebar-icon', ['icon' => 'clock'])
                        </span>
                    </span>
                    <p class="text-sm text-slate-500">{{ __('No slaughter activity recorded yet.') }}</p>
                </div>
            @endforelse
        </section>

        <section class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach ([
                ['route' => 'rica.slaughterhouses.index', 'label' => __('All slaughterhouses'), 'icon' => 'building', 'desc' => __('Browse every registered facility')],
                ['route' => 'rica.reports', 'label' => __('Reports'), 'icon' => 'chart', 'desc' => __('Slaughter and inspection summaries')],
                ['route' => 'super-admin.dashboard', 'label' => __('Platform dashboard'), 'icon' => 'dashboard', 'desc' => __('Return to super admin overview')],
            ] as $link)
                <a href="{{ route($link['route']) }}"
                   class="group flex items-start gap-3 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:border-bucha-primary/25 hover:bg-slate-50/60 hover:shadow-md">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200 group-hover:bg-bucha-primary/10 group-hover:text-bucha-primary group-hover:ring-bucha-primary/20" aria-hidden="true">
                        <span class="[&>svg]:h-5 [&>svg]:w-5">
                            @include('layouts.partials.sidebar-icon', ['icon' => $link['icon']])
                        </span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800 group-hover:text-bucha-primary">{{ $link['label'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $link['desc'] }}</p>
                    </div>
                </a>
            @endforeach
        </section>
    </div>
</x-app-layout>
