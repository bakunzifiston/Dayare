@props(['status' => ''])

@php
    $styles = match (strtolower((string) $status)) {
        'paid' => 'bg-emerald-100 text-emerald-800',
        'partial' => 'bg-amber-100 text-amber-900',
        'pending' => 'bg-slate-100 text-slate-700',
        'overdue' => 'bg-red-100 text-red-800',
        'refunded' => 'bg-violet-100 text-violet-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize '.$styles]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
