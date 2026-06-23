@props([
    'tone' => 'default',
    'label' => '',
])

<span {{ $attributes->merge(['class' => match ($tone) {
    'muted' => 'entity-badge entity-badge--muted',
    'alert' => 'entity-badge entity-badge--alert',
    default => 'entity-badge',
}]) }}>{{ $label }}</span>
