<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <h2 class="font-semibold text-xl text-slate-800">{{ __('Farms') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Manage farm profiles, livestock groups, and health records.') }}</p>
            </div>
            <a href="{{ route('farmer.farms.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:opacity-95">
                {{ __('Add farm') }}
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl space-y-6">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Total farms') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($metrics['total_farms']) }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Active farms') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-700">{{ number_format($metrics['active_farms']) }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Livestock groups') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($metrics['livestock_groups']) }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Total headcount') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($metrics['total_headcount']) }}</p>
            </div>
        </div>

        @if ($farms->isEmpty())
            <div class="rounded-bucha border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                <p class="text-sm text-slate-600">{{ __('No farms yet. Create your first farm to register livestock and start traceability.') }}</p>
                <a href="{{ route('farmer.farms.create') }}" class="mt-4 inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Add farm') }}</a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($farms as $farm)
                    @php
                        $location = collect([$farm->district?->name, $farm->sector?->name, $farm->village?->name])->filter()->implode(' · ');
                        $statusStyles = match ($farm->status) {
                            \App\Models\Farm::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-800',
                            \App\Models\Farm::STATUS_SUSPENDED => 'bg-amber-100 text-amber-900',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        $animalTypes = collect($farm->animal_types ?? [])->map(fn ($type) => \App\Support\FarmerAnimalType::label($type));
                    @endphp
                    <article class="flex h-full flex-col rounded-bucha border border-slate-200 bg-white p-5 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('farmer.farms.show', $farm) }}" class="block truncate text-lg font-semibold text-slate-900 hover:text-bucha-primary">{{ $farm->name }}</a>
                                <p class="mt-1 truncate text-sm text-slate-500">{{ $farm->business?->business_name ?: __('Unassigned business') }}</p>
                            </div>
                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $statusStyles }}">{{ __(ucfirst($farm->status)) }}</span>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Registration') }}</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $farm->registration_number ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Farm size') }}</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $farm->farm_size_hectares !== null ? number_format((float) $farm->farm_size_hectares, 2).' '.__('ha') : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Livestock groups') }}</dt>
                                <dd class="mt-1 font-medium tabular-nums text-slate-900">{{ number_format((int) $farm->livestock_count) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Headcount') }}</dt>
                                <dd class="mt-1 font-medium tabular-nums text-slate-900">{{ number_format((int) ($farm->total_headcount ?? 0)) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 space-y-2 text-sm">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Location') }}</p>
                            <p class="text-slate-700">{{ $location !== '' ? $location : __('Location not recorded') }}</p>
                            @if ($animalTypes->isNotEmpty())
                                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Animal types') }}</p>
                                <p class="text-slate-700">{{ $animalTypes->take(3)->join(', ') }}@if ($animalTypes->count() > 3) {{ __('and :count more', ['count' => $animalTypes->count() - 3]) }}@endif</p>
                            @endif
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                            <a href="{{ route('farmer.farms.show', $farm) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50">{{ __('View') }}</a>
                            <a href="{{ route('farmer.farms.livestock.index', $farm) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-bucha-primary transition hover:bg-slate-50">{{ __('Livestock') }}</a>
                            <a href="{{ route('farmer.farms.health-records.index', $farm) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50">{{ __('Health') }}</a>
                            <a href="{{ route('farmer.farms.edit', $farm) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50">{{ __('Edit') }}</a>
                            <form method="post" action="{{ route('farmer.farms.destroy', $farm) }}" class="inline-flex" onsubmit="return confirm('{{ __('Delete this farm?') }}');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-700 transition hover:bg-red-50">{{ __('Delete') }}</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            <div>{{ $farms->links() }}</div>
        @endif
    </div>
</x-app-layout>
