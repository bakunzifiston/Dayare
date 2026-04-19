@props(['status' => 'pending'])

@php
    $normalized = mb_strtolower((string) $status);
    $styles = match ($normalized) {
        'approved', 'delivered', 'valid', 'paid', 'available', 'confirmed', 'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'partially_paid' => 'border-amber-200 bg-amber-50 text-amber-700',
        'rejected', 'failed', 'expired', 'cancelled', 'overdue' => 'border-rose-200 bg-rose-50 text-rose-700',
        'in_transit', 'loading', 'loaded', 'in_use', 'assigned', 'in_progress', 'at_checkpoint', 'delayed', 'arrived' => 'border-amber-200 bg-amber-50 text-amber-700',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium', $styles]) }}>
    {{ str_replace('_', ' ', ucfirst($normalized)) }}
</span>
