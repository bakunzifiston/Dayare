@props([
    'href' => null,
    'variant' => 'default',
])

@php
    $class = match ($variant) {
        'danger' => 'profile-action profile-action--danger',
        default => 'profile-action',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
