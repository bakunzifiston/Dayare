@props(['status'])

@php
    $normalized = strtolower((string) $status);
    $classes = match ($normalized) {
        'draft' => 'bg-slate-100 text-slate-700',
        'sent' => 'bg-blue-100 text-blue-800',
        'confirmed' => 'bg-indigo-100 text-indigo-800',
        'delivered', 'good', 'in_storage', 'open', 'completed' => 'bg-emerald-100 text-emerald-800',
        'closed', 'pending' => 'bg-slate-100 text-slate-700',
        'pass' => 'bg-emerald-100 text-emerald-800',
        'fail' => 'bg-red-100 text-red-800',
        'partial' => 'bg-amber-100 text-amber-900',
        'partially_used' => 'bg-amber-100 text-amber-900',
        'disposed', 'expired' => 'bg-red-100 text-red-800',
        'fully_used' => 'bg-slate-100 text-slate-700',
        'cancelled', 'rejected' => 'bg-red-100 text-red-800',
        'fair' => 'bg-amber-100 text-amber-900',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {$classes}"]) }}>
    {{ str_replace('_', ' ', ucfirst($normalized)) }}
</span>
