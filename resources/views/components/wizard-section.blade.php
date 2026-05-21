@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'bucha-wizard-section']) }}>
    @if ($title)
        <header class="bucha-wizard-section__head">
            <h4 class="bucha-wizard-section__title">{{ $title }}</h4>
            @if ($subtitle)
                <p class="bucha-wizard-section__subtitle">{{ $subtitle }}</p>
            @endif
        </header>
    @endif
    <div class="bucha-wizard-section__body">
        {{ $slot }}
    </div>
</section>
