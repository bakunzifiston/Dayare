@props(['status'])

@php
    $classes = match ($status) {
        'pending' => 'bg-slate-100 text-slate-700',
        'in_transit' => 'bg-amber-100 text-amber-800',
        'arrived' => 'bg-emerald-100 text-emerald-800',
        'completed' => 'bg-blue-100 text-blue-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold '.$classes]) }}>
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
