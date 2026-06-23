@props(['value', 'label'])

<div {{ $attributes->merge(['class' => 'profile-card__highlight']) }}>
    <p class="profile-card__highlight-value">{{ $value }}</p>
    <p class="profile-card__highlight-label">{{ $label }}</p>
</div>
