@props([
    'label' => '',
    'value' => '',
    'subtext' => null,
    'trend' => null,
    'trendText' => null,
    'color' => null,
    'icon' => null,
])

@php
    $valueColors = [
        'success' => 'text-emerald-600',
        'warning' => 'text-amber-600',
        'danger' => 'text-red-600',
    ];
    $valueColorClass = $color ? ($valueColors[$color] ?? 'text-slate-900') : 'text-slate-900';
    $trendColors = [
        'up' => 'text-emerald-600',
        'down' => 'text-red-600',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm h-full']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-1.5 text-xl font-semibold tabular-nums leading-tight tracking-tight {{ $valueColorClass }}">
                {{ $value }}
            </p>
            @if ($subtext)
                <p class="mt-1 text-[11px] leading-snug text-slate-500">{{ $subtext }}</p>
            @endif
            @if ($trend && $trendText)
                <p class="mt-1.5 text-[11px] font-medium {{ $trendColors[$trend] ?? 'text-slate-500' }}">{{ $trendText }}</p>
            @endif
        </div>
        @if ($icon)
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-500 ring-1 ring-inset ring-slate-200" aria-hidden="true">
                <i class="{{ $icon }} text-[1.25rem] leading-none"></i>
            </span>
        @endif
    </div>
</div>
