@props([
    'tone' => 'default',
    'label' => '',
])

@php
    $class = match ($tone) {
        'active' => 'profile-pill profile-pill--active',
        'warning' => 'profile-pill profile-pill--warning',
        'danger' => 'profile-pill profile-pill--danger',
        'muted' => 'profile-pill profile-pill--muted',
        default => 'profile-pill',
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>{{ $label }}</span>
