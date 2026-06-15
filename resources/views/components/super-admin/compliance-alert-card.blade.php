@props([
    'label' => '',
    'description' => '',
    'count' => 0,
    'severity' => 'amber',
    'icon' => 'clipboard',
    'href' => null,
])

@php
    $severityStyles = [
        'green' => 'border-emerald-200 bg-emerald-50/80 text-emerald-900',
        'amber' => 'border-amber-200 bg-amber-50/80 text-amber-900',
        'red' => 'border-red-200 bg-red-50/80 text-red-900',
    ];
    $countStyles = [
        'green' => 'text-emerald-700',
        'amber' => 'text-amber-700',
        'red' => 'text-red-700',
    ];
    $cardClass = $severityStyles[$severity] ?? $severityStyles['amber'];
    $countClass = $countStyles[$severity] ?? $countStyles['amber'];
@endphp

@if ($href)
    <a href="{{ $href }}" class="block rounded-xl border px-4 py-4 transition-shadow hover:shadow-md {{ $cardClass }}">
@else
    <div class="rounded-xl border px-4 py-4 {{ $cardClass }}">
@endif
    <div class="flex items-start gap-3">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/70 ring-1 ring-inset ring-black/5" aria-hidden="true">
            <span class="[&>svg]:h-4 [&>svg]:w-4">
                @include('layouts.partials.sidebar-icon', ['icon' => $icon])
            </span>
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold tabular-nums {{ $countClass }}">{{ number_format((int) $count) }}</p>
            <p class="mt-1 text-xs leading-relaxed opacity-80">{{ $description }}</p>
            @if ($href && $count > 0)
                <p class="mt-2 text-[11px] font-semibold underline-offset-2 hover:underline">{{ __('View list') }} →</p>
            @endif
        </div>
    </div>
@if ($href)
    </a>
@else
    </div>
@endif
