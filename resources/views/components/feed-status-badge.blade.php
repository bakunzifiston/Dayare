@props(['status' => ''])

@php
    $normalized = strtolower(str_replace(' ', '_', (string) $status));
    $styles = match (true) {
        in_array($normalized, ['active', 'available'], true) => 'bg-emerald-100 text-emerald-800',
        in_array($normalized, ['low_stock', 'twice_daily'], true) => 'bg-amber-100 text-amber-900',
        in_array($normalized, ['inactive', 'out_of_stock', 'expired'], true) => 'bg-red-100 text-red-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize '.$styles]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
