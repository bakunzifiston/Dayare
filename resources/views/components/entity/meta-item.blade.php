@props(['label', 'wide' => false])

<div {{ $attributes->merge(['class' => 'entity-card__meta-item'.($wide ? ' entity-card__meta-item--wide' : '')]) }}>
    <dt class="entity-card__label">{{ $label }}</dt>
    <dd class="entity-card__value">{{ $slot }}</dd>
</div>
