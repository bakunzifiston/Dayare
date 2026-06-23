@props([
    'href',
    'variant' => 'secondary',
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'entity-action entity-action--'.$variant]) }}>
    {{ $slot }}
</a>
