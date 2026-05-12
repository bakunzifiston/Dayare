<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Feeding record') }}</h2></x-slot>
    <div class="max-w-4xl space-y-6">@include('farmer.feeding.partials.nav')<section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm"><p class="text-xs uppercase tracking-wide text-slate-500">{{ $record->feeding_code }}</p><h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $record->feedType?->feed_name }}</h3><p class="mt-2 text-slate-600">{{ number_format((float) $record->quantity, 2) }} · {{ $record->feeding_date?->toDateString() }}</p><p class="mt-2">{{ $record->animal?->animal_code ?: $record->livestock?->livestock_name }}</p></section></div>
</x-app-layout>
