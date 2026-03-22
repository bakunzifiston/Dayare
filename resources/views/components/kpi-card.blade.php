@props([
    'title' => '',
    'value' => 0,
    'subtitle' => null,
    'href' => null,
    'color' => 'blue',
    'inline' => false,
    /** Unified vertical stat tile (e.g. dashboard Key metrics) */
    'stat' => false,
])

@php
    $colors = [
        'blue'   => 'bg-blue-50/80 text-blue-900 border-blue-100',
        'green'  => 'bg-emerald-50/80 text-emerald-900 border-emerald-100',
        'amber'  => 'bg-amber-50/80 text-amber-900 border-amber-100',
        'slate'  => 'bg-slate-50/80 text-slate-700 border-slate-100',
        'bucha'  => 'bg-red-50/60 text-bucha-burgundy border-red-100',
        'bucha-muted' => 'bg-slate-50/90 text-slate-700 border-slate-200/80',
        'bucha-success' => 'bg-emerald-50/80 text-emerald-900 border-emerald-100',
    ];
    $border = $colors[$color] ?? $colors['blue'];
    $baseClass = $stat
        ? 'rounded-lg border border-slate-200/90 bg-white p-2.5 sm:p-3 shadow-sm'
        : ($inline
            ? 'rounded-lg border px-3.5 py-2 shrink-0 text-sm ' . $border
            : 'rounded-xl border p-5 shadow-sm ' . $border);
    $statInteractive = $stat && $href
        ? 'transition-all duration-200 hover:border-bucha-primary/30 hover:bg-slate-50/80 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-bucha-primary/40 focus-visible:ring-offset-2 group'
        : '';
    $linkClass = ! $stat && $href ? 'block transition-all duration-150 hover:shadow hover:opacity-95' : '';
    $displayValue = is_numeric($value) ? number_format((int) $value) : $value;
@endphp

@if ($stat)
    @if ($href)
        <a
            href="{{ $href }}"
            {{ $attributes->merge(['class' => $baseClass . ' block ' . $statInteractive]) }}
        >
    @else
        <div {{ $attributes->merge(['class' => $baseClass]) }}>
    @endif
        <div class="flex gap-2 items-start min-w-0">
            @if (isset($icon) && $icon->isNotEmpty())
                <span class="shrink-0 inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-md bg-bucha-primary/10 text-bucha-primary" aria-hidden="true">
                    <span class="[&>svg]:h-3.5 [&>svg]:w-3.5 sm:[&>svg]:h-4 sm:[&>svg]:w-4">{{ $icon }}</span>
                </span>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-[0.65rem] sm:text-[0.7rem] font-medium uppercase tracking-wide text-bucha-muted leading-tight">{{ $title }}</p>
                <p class="mt-1 text-lg sm:text-xl font-bold tabular-nums tracking-tight text-slate-900 leading-none">{{ $displayValue }}</p>
                @if ($subtitle !== null && $subtitle !== '')
                    <p class="mt-0.5 text-[0.65rem] sm:text-[11px] text-slate-500 leading-snug">{{ $subtitle }}</p>
                @endif
                @if ($href)
                    <p class="mt-1.5 text-[0.65rem] font-medium text-bucha-primary opacity-0 transition-opacity group-hover:opacity-100 max-sm:hidden">{{ __('View') }} →</p>
                @endif
            </div>
        </div>
    @if ($href)
        </a>
    @else
        </div>
    @endif
@else
    <div {{ $attributes->merge(['class' => $baseClass . ($href ? ' hover:shadow-md' : '')]) }}>
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
