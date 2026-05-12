<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <h2 class="font-semibold text-xl text-slate-800">{{ __('Animals') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Individual animals across all farms and livestock groups.') }}</p>
            </div>
            <a href="{{ route('farmer.livestock.index') }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-50">{{ __('Livestock groups') }}</a>
        </div>
    </x-slot>

    <div class="max-w-7xl space-y-6">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['total', __('Total animals')], ['active', __('Active')], ['sick', __('Sick')], ['ready_for_sale', __('Ready for sale')]] as [$key, $label])
                <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format((int) $stats[$key]) }}</p>
                </div>
            @endforeach
        </div>

        <form method="get" class="grid gap-3 rounded-bucha border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search tag, code, or name') }}" class="rounded-lg border-gray-300 text-sm md:col-span-2">
            <select name="lifecycle_status" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('All lifecycle statuses') }}</option>
                @foreach (\App\Models\Animal::LIFECYCLE_STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('lifecycle_status') === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                @endforeach
            </select>
            <select name="health_status" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('All health statuses') }}</option>
                @foreach (\App\Models\Animal::HEALTH_STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('health_status') === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">{{ __('Filter') }}</button>
        </form>

        @if ($animals->isEmpty())
            <div class="rounded-bucha border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                <p class="text-sm text-slate-600">{{ __('No individual animals recorded yet. Create a farm and livestock group, then add animals from the group page.') }}</p>
                <div class="mt-4 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('farmer.farms.index') }}" class="inline-flex items-center rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Manage farms') }}</a>
                    <a href="{{ route('farmer.livestock.index') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Browse livestock') }}</a>
                </div>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($animals as $animal)
                    @php
                        $livestock = $animal->livestock;
                        $farm = $livestock?->farm;
                        $lifecycleStyles = match ($animal->lifecycle_status) {
                            \App\Models\Animal::LIFECYCLE_ACTIVE => 'bg-emerald-100 text-emerald-800',
                            \App\Models\Animal::LIFECYCLE_SOLD => 'bg-sky-100 text-sky-800',
                            \App\Models\Animal::LIFECYCLE_DEAD => 'bg-slate-200 text-slate-700',
                            default => 'bg-amber-100 text-amber-900',
                        };
                    @endphp
                    <article class="flex h-full flex-col rounded-bucha border border-slate-200 bg-white p-5 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('farmer.farms.livestock.animals.show', [$farm, $livestock, $animal]) }}" class="block truncate text-lg font-semibold text-slate-900 hover:text-bucha-primary">{{ $animal->animal_name ?: $animal->animal_code }}</a>
                                <p class="mt-1 font-mono text-xs text-slate-500">{{ $animal->animal_code }}</p>
                            </div>
                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $lifecycleStyles }}">{{ str_replace('_', ' ', $animal->lifecycle_status) }}</span>
                        </div>

                        <div class="mt-3">
                            <x-health-status-badge :status="$animal->health_status" />
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Tag') }}</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $animal->tag_number ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Gender') }}</dt>
                                <dd class="mt-1 font-medium capitalize text-slate-900">{{ $animal->gender }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Farm') }}</dt>
                                <dd class="mt-1 font-medium text-slate-900">
                                    @if ($farm)
                                        <a href="{{ route('farmer.farms.show', $farm) }}" class="text-bucha-primary hover:underline">{{ $farm->name }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Livestock group') }}</dt>
                                <dd class="mt-1 font-medium text-slate-900">
                                    @if ($livestock && $farm)
                                        <a href="{{ route('farmer.farms.livestock.show', [$farm, $livestock]) }}" class="text-bucha-primary hover:underline">{{ $livestock->livestock_name }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Production') }}</dt>
                                <dd class="mt-1 font-medium capitalize text-slate-900">{{ str_replace('_', ' ', $animal->production_status) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                            <a href="{{ route('farmer.farms.livestock.animals.show', [$farm, $livestock, $animal]) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50">{{ __('View') }}</a>
                            <a href="{{ route('farmer.farms.livestock.animals.edit', [$farm, $livestock, $animal]) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-bucha-primary transition hover:bg-slate-50">{{ __('Edit') }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div>{{ $animals->links() }}</div>
        @endif
    </div>
</x-app-layout>
