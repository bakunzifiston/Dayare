@props(['status' => 'pending'])

@php
    $normalized = mb_strtolower((string) $status);
    $styles = match ($normalized) {
        'approved', 'delivered', 'valid', 'paid', 'available' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'rejected', 'failed', 'expired' => 'border-rose-200 bg-rose-50 text-rose-700',
        'in_transit', 'loading', 'in_use', 'assigned' => 'border-amber-200 bg-amber-50 text-amber-700',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium', $styles]) }}>
    {{ str_replace('_', ' ', ucfirst($normalized)) }}
</span>
