@props([
    'title' => '',
    'value' => 0,
    'subtitle' => null,
    'href' => null,
    'color' => 'blue',
    'inline' => false,
])

@php
    $colors = [
        'blue'   => 'bg-blue-50/80 text-blue-900 border-blue-100',
        'green'  => 'bg-emerald-50/80 text-emerald-900 border-emerald-100',
        'amber'  => 'bg-amber-50/80 text-amber-900 border-amber-100',
        'slate'  => 'bg-slate-50/80 text-slate-700 border-slate-100',
    ];
    $border = $colors[$color] ?? $colors['blue'];
    $baseClass = $inline
        ? 'rounded-lg border px-3.5 py-2 shrink-0 text-sm ' . $border
        : 'rounded-xl border p-5 shadow-sm ' . $border;
    $linkClass = $href ? 'block transition-all duration-150 hover:shadow hover:opacity-95' : '';
@endphp

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
