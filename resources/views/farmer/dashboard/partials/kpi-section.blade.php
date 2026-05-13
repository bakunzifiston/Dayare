@props([
    'sectionId',
    'title',
    'cards',
    'grid' => 'lg:grid-cols-4',
])

<section aria-labelledby="{{ $sectionId }}">
    <h2 id="{{ $sectionId }}" class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $title }}</h2>
    <div class="grid grid-cols-2 gap-2.5 sm:gap-3 {{ $grid }}">
        @foreach ($cards as $card)
            <x-kpi-card
                stat
                :glyph="$card['glyph']"
                :title="$card['title']"
                :value="$card['value']"
                :color="$card['color']"
                :href="$card['href'] ?? null"
            />
        @endforeach
    </div>
</section>
