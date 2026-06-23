<article {{ $attributes->merge(['class' => 'entity-card']) }}>
    <div class="entity-card__top">
        <div class="entity-card__title-row">
            <div class="entity-card__headline">
                <h3 class="entity-card__title">
                    {!! $title !!}
                </h3>
                @if (isset($subtitle))
                    <p class="entity-card__subtitle">{{ $subtitle }}</p>
                @endif
                @if (isset($badges))
                    <p class="entity-card__meta-line">{{ $badges }}</p>
                @endif
            </div>
            @if (isset($stat))
                <div class="entity-card__stat">
                    {{ $stat }}
                </div>
            @endif
        </div>
    </div>

    @if ($slot->isNotEmpty())
        <div class="entity-card__body">
            {{ $slot }}
        </div>
    @endif

    @if (isset($actions))
        <div class="entity-card__actions">
            {{ $actions }}
        </div>
    @endif
</article>
