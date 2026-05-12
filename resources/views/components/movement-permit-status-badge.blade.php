@props(['status' => ''])

@php
    $styles = match (strtolower((string) $status)) {
        'draft' => 'bg-slate-100 text-slate-700',
        'pending_approval' => 'bg-amber-100 text-amber-900',
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-red-100 text-red-800',
        'expired' => 'bg-violet-100 text-violet-800',
        'cancelled' => 'bg-slate-200 text-slate-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize '.$styles]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
