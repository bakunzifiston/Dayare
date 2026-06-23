@props(['label'])

<div {{ $attributes->merge(['class' => 'profile-card__row']) }}>
    <span class="profile-card__row-label">{{ $label }}</span>
    <span class="profile-card__row-value">{{ $slot }}</span>
</div>
