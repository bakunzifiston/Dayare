@props([
    'label' => '',
    'value' => '',
    'subtext' => null,
    'trend' => null,
    'trendText' => null,
    'color' => null,
    'tone' => null,
    'icon' => null,
])

@php
    $valueColors = [
        'success' => 'text-emerald-700',
        'warning' => 'text-amber-700',
        'danger' => 'text-red-700',
    ];
    $valueColorClass = $color ? ($valueColors[$color] ?? 'text-slate-900') : 'text-slate-900';
    $trendColors = [
        'up' => 'text-emerald-600',
        'down' => 'text-red-600',
    ];

    // Prefer explicit tone; otherwise map semantic color → accent palette.
    $resolvedTone = $tone ?? match ($color) {
        'success' => 'emerald',
        'warning' => 'amber',
        'danger' => 'rose',
        default => null,
    };

    $tones = [
        'rose' => [
            'card' => 'border-rose-100/90 bg-gradient-to-br from-rose-50/90 via-white to-white',
            'icon' => 'bg-rose-100 text-rose-700 ring-rose-200/80',
            'label' => 'text-rose-700/80',
        ],
        'emerald' => [
            'card' => 'border-emerald-100/90 bg-gradient-to-br from-emerald-50/90 via-white to-white',
            'icon' => 'bg-emerald-100 text-emerald-700 ring-emerald-200/80',
            'label' => 'text-emerald-700/80',
        ],
        'amber' => [
            'card' => 'border-amber-100/90 bg-gradient-to-br from-amber-50/90 via-white to-white',
            'icon' => 'bg-amber-100 text-amber-700 ring-amber-200/80',
            'label' => 'text-amber-700/80',
        ],
        'sky' => [
            'card' => 'border-sky-100/90 bg-gradient-to-br from-sky-50/90 via-white to-white',
            'icon' => 'bg-sky-100 text-sky-700 ring-sky-200/80',
            'label' => 'text-sky-700/80',
        ],
        'indigo' => [
            'card' => 'border-indigo-100/90 bg-gradient-to-br from-indigo-50/90 via-white to-white',
            'icon' => 'bg-indigo-100 text-indigo-700 ring-indigo-200/80',
            'label' => 'text-indigo-700/80',
        ],
        'slate' => [
            'card' => 'border-slate-200/80 bg-gradient-to-br from-slate-50 via-white to-white',
            'icon' => 'bg-slate-100 text-slate-600 ring-slate-200',
            'label' => 'text-slate-500',
        ],
        'bucha' => [
            'card' => 'border-red-100/90 bg-gradient-to-br from-red-50/80 via-white to-white',
            'icon' => 'bg-red-100 text-bucha-burgundy ring-red-200/80',
            'label' => 'text-bucha-burgundy/80',
        ],
        'teal' => [
            'card' => 'border-teal-100/90 bg-gradient-to-br from-teal-50/90 via-white to-white',
            'icon' => 'bg-teal-100 text-teal-700 ring-teal-200/80',
            'label' => 'text-teal-700/80',
        ],
    ];

    $toneStyles = $tones[$resolvedTone] ?? [
        'card' => 'border-slate-200/80 bg-white',
        'icon' => 'bg-slate-50 text-slate-500 ring-slate-200',
        'label' => 'text-slate-500',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border p-4 shadow-sm h-full '.$toneStyles['card']]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium {{ $toneStyles['label'] }}">{{ $label }}</p>
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
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset {{ $toneStyles['icon'] }}" aria-hidden="true">
                <i class="{{ $icon }} text-[1.25rem] leading-none"></i>
            </span>
        @endif
    </div>
</div>
