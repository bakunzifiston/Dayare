<article {{ $attributes->merge(['class' => 'profile-card']) }}>
    <div class="profile-card__inner">
        <div class="profile-card__header">
            <div class="profile-card__identity">
                @if (isset($avatar))
                    <div class="profile-card__avatar" aria-hidden="true">{{ $avatar }}</div>
                @endif
                <div class="profile-card__titles">
                    <h3 class="profile-card__title">{!! $title !!}</h3>
                    @if (isset($subtitle))
                        <p class="profile-card__subtitle">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @if (isset($badge))
                <div class="profile-card__badge-wrap">{{ $badge }}</div>
            @endif
        </div>

        @if ($slot->isNotEmpty())
            <div class="profile-card__rows">
                {{ $slot }}
            </div>
        @endif

        @if (isset($highlights))
            <div class="profile-card__highlights">
                {{ $highlights }}
            </div>
        @endif

        @if (isset($actions))
            <div class="profile-card__actions">
                {{ $actions }}
            </div>
        @endif
    </div>
</article>
