<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.livestock.animals.index', [$farm, $livestock]) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Animals') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ $animal->animal_name ?: $animal->animal_code }}</h2>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[240px,1fr]">
            <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
                @if ($animal->photoUrl())
                    <img src="{{ $animal->photoUrl() }}" alt="{{ $animal->animal_name ?: $animal->animal_code }}" class="aspect-square w-full rounded-lg object-cover">
                @else
                    <div class="flex aspect-square items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-500">{{ __('No photo') }}</div>
                @endif
                <p class="mt-3 text-xs text-slate-500">{{ __('QR payload') }}</p>
                <p class="mt-1 break-all text-sm font-mono text-slate-800">{{ $animal->qr_code }}</p>
            </div>

            <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div><dt class="text-slate-500">{{ __('Animal code') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $animal->animal_code }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Tag number') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $animal->tag_number ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Gender') }}</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ $animal->gender }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Birth date') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $animal->birth_date?->toDateString() ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Weight') }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $animal->weight !== null ? number_format((float) $animal->weight, 2).' kg' : '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Health status') }}</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ str_replace('_', ' ', $animal->health_status) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Production status') }}</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ $animal->production_status ? str_replace('_', ' ', $animal->production_status) : '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Lifecycle status') }}</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ str_replace('_', ' ', $animal->lifecycle_status) }}</dd></div>
                </dl>
            </section>
        </div>

        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-slate-900">{{ __('Health timeline') }}</h3>
                <a href="{{ route('farmer.health.timeline.index', ['animal_id' => $animal->id]) }}" class="text-sm text-bucha-primary hover:underline">{{ __('Open full timeline') }}</a>
            </div>
            <div class="mt-4">@include('farmer.health.partials.timeline', ['events' => $healthTimeline])</div>
        </section>

        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-slate-900">{{ __('Certificates & verification') }}</h3>
                <a href="{{ route('farmer.certificates.animal-certificates.create', ['animal_id' => $animal->id]) }}" class="text-sm text-bucha-primary hover:underline">{{ __('Issue certificate') }}</a>
            </div>
            <p class="mt-3 text-sm text-slate-600">{{ __('Public verification') }}: <a href="{{ $animal->publicVerificationUrl() }}" class="break-all text-bucha-primary hover:underline" target="_blank">{{ $animal->publicVerificationUrl() }}</a></p>
        </section>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('farmer.health.vaccinations.create', ['animal_id' => $animal->id]) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Add vaccination') }}</a>
            <a href="{{ route('farmer.health.treatments.create', ['animal_id' => $animal->id]) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Add treatment') }}</a>
            <a href="{{ route('farmer.farms.livestock.animals.edit', [$farm, $livestock, $animal]) }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Edit animal') }}</a>
        </div>
    </div>
</x-app-layout>
