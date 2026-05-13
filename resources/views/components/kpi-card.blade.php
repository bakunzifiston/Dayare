@props([
    'title' => '',
    'value' => 0,
    'subtitle' => null,
    'href' => null,
    'color' => 'blue',
    'glyph' => null,
    'inline' => false,
    /** Unified vertical stat tile (e.g. dashboard Key metrics) */
    'stat' => false,
])

@php
    $colors = [
        'blue' => 'bg-blue-50/80 text-blue-900 border-blue-100',
        'green' => 'bg-emerald-50/80 text-emerald-900 border-emerald-100',
        'amber' => 'bg-amber-50/80 text-amber-900 border-amber-100',
        'slate' => 'bg-slate-50/80 text-slate-700 border-slate-100',
        'bucha' => 'bg-red-50/60 text-bucha-burgundy border-red-100',
        'bucha-muted' => 'bg-slate-50/90 text-slate-700 border-slate-200/80',
        'bucha-success' => 'bg-emerald-50/80 text-emerald-900 border-emerald-100',
    ];
    $statIconColors = [
        'blue' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100',
        'green' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-100',
        'amber' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-100',
        'slate' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
        'bucha' => 'bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100',
        'bucha-muted' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
        'bucha-success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-100',
    ];
    $border = $colors[$color] ?? $colors['blue'];
    $statIconClass = $statIconColors[$color] ?? $statIconColors['blue'];
    $baseClass = $stat
        ? 'rounded-xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-sm'
        : ($inline
            ? 'rounded-lg border px-3.5 py-2 shrink-0 text-sm '.$border
            : 'rounded-xl border p-5 shadow-sm '.$border);
    $statInteractive = $stat && $href
        ? 'transition-all duration-200 hover:border-bucha-primary/25 hover:bg-slate-50/60 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-bucha-primary/40 focus-visible:ring-offset-2 group'
        : '';
    $linkClass = ! $stat && $href ? 'block transition-all duration-150 hover:shadow hover:opacity-95' : '';
    $displayValue = is_numeric($value) ? number_format((int) $value) : $value;
    $hasIconSlot = isset($icon) && $icon->isNotEmpty();
    $hasGlyph = $glyph !== null && $glyph !== '';
    $showIcon = $hasIconSlot || $hasGlyph;
@endphp

@if ($stat)
    @if ($href)
        <a
            href="{{ $href }}"
            {{ $attributes->merge(['class' => $baseClass.' block h-full '.$statInteractive]) }}
        >
    @else
        <div {{ $attributes->merge(['class' => $baseClass.' h-full']) }}>
    @endif
        <div class="flex h-full items-start gap-3 min-w-0">
            @if ($showIcon)
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $statIconClass }}" aria-hidden="true">
                    <span class="[&>svg]:h-4 [&>svg]:w-4">
                        @if ($hasIconSlot)
                            {{ $icon }}
                        @else
                            @include('layouts.partials.sidebar-icon', ['icon' => $glyph])
                        @endif
                    </span>
                </span>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium leading-snug text-slate-500">{{ $title }}</p>
                <p class="mt-1.5 line-clamp-2 text-lg font-semibold tabular-nums leading-tight tracking-tight text-slate-900 sm:text-xl" title="{{ $displayValue }}">{{ $displayValue }}</p>
                @if ($subtitle !== null && $subtitle !== '')
                    <p class="mt-1 text-[11px] leading-snug text-slate-500">{{ $subtitle }}</p>
                @endif
                @if ($href)
                    <p class="mt-2 text-[11px] font-medium text-bucha-primary opacity-0 transition-opacity group-hover:opacity-100 max-sm:hidden">{{ __('View details') }}</p>
                @endif
            </div>
        </div>
    @if ($href)
        </a>
    @else
        </div>
    @endif
@else
    <div {{ $attributes->merge(['class' => $baseClass.($href ? ' hover:shadow-md' : '')]) }}>
        @if ($href)
            <a href="{{ $href }}" class="{{ $linkClass }}">
        @endif
        @if ($inline)
            <span class="text-slate-600 font-medium">{{ $title }}</span>
            <span class="ml-2 text-base font-semibold tabular-nums text-slate-900">{{ $value }}</span>
            @if ($subtitle !== null && $subtitle !== '')
                <span class="ml-1.5 text-xs text-slate-500">· {{ $subtitle }}</span>
            @endif
        @else
            <p class="text-sm font-medium text-slate-600">{{ $title }}</p>
            <p class="mt-1.5 text-2xl font-bold tabular-nums tracking-tight text-slate-900">{{ $value }}</p>
            @if ($subtitle !== null && $subtitle !== '')
                <p class="mt-1 text-xs text-slate-500">{{ $subtitle }}</p>
            @endif
        @endif
        @if ($href)
            </a>
        @endif
    </div>
@endif
