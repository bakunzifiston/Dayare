<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <a href="{{ route('farmer.farms.show', $farm) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← :farm', ['farm' => $farm->name]) }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Livestock groups') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Manage herd groups, animal records, and health status for this farm.') }}</p>
            </div>
            <a href="{{ route('farmer.farms.livestock.create', $farm) }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:opacity-95">{{ __('Add group') }}</a>
        </div>
    </x-slot>

    <div class="max-w-7xl space-y-6">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['groups', __('Groups')], ['headcount', __('Headcount')], ['active_groups', __('Active groups')], ['quarantined_groups', __('Quarantined')]] as [$key, $label])
                <div class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format((int) $stats[$key]) }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-bucha border border-slate-200 bg-white p-4 text-sm shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Herd health split') }}</p>
            <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1">
                <span><span class="text-slate-500">{{ __('Healthy') }}</span> <strong class="text-emerald-800 tabular-nums">{{ number_format((int) $healthHeadcounts['healthy']) }}</strong></span>
                <span><span class="text-slate-500">{{ __('Sick') }}</span> <strong class="text-red-800 tabular-nums">{{ number_format((int) $healthHeadcounts['sick']) }}</strong></span>
                @if (($healthHeadcounts['unrecorded'] ?? 0) > 0)
                    <span><span class="text-slate-500">{{ __('Unassigned') }}</span> <strong class="text-amber-800 tabular-nums">{{ number_format((int) $healthHeadcounts['unrecorded']) }}</strong></span>
                @endif
            </div>
        </div>

        <form method="get" class="grid gap-3 rounded-bucha border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search name or code') }}" class="rounded-lg border-gray-300 text-sm md:col-span-2">
            <select name="status" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Models\Livestock::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst($status)) }}</option>
                @endforeach
            </select>
            <select name="health_status" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('All health statuses') }}</option>
                @foreach (\App\Models\Livestock::HEALTH_STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('health_status') === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">{{ __('Filter') }}</button>
        </form>

        @if ($livestock->isEmpty())
            <div class="rounded-bucha border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                <p class="text-sm text-slate-600">{{ __('No livestock groups yet. Create a group to start recording animals under this farm.') }}</p>
                <a href="{{ route('farmer.farms.livestock.create', $farm) }}" class="mt-4 inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Create first group') }}</a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($livestock as $row)
                    @php
                        $quality = $row->qualityScore();
                        $statusStyles = match ($row->status) {
                            \App\Models\Livestock::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-800',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        $lifecycleStyles = match ($row->lifecycle_status) {
                            \App\Models\Livestock::LIFECYCLE_QUARANTINED => 'bg-amber-100 text-amber-900',
                            \App\Models\Livestock::LIFECYCLE_CLOSED => 'bg-slate-200 text-slate-700',
                            default => 'bg-sky-100 text-sky-800',
                        };
                        $qualityStyles = match ($quality['tier']) {
                            'A' => 'bg-emerald-100 text-emerald-900',
                            'B' => 'bg-amber-100 text-amber-900',
                            default => 'bg-slate-200 text-slate-800',
                        };
                    @endphp
                    <article class="flex h-full flex-col rounded-bucha border border-slate-200 bg-white p-5 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('farmer.farms.livestock.show', [$farm, $row]) }}" class="block truncate text-lg font-semibold text-slate-900 hover:text-bucha-primary">{{ $row->livestock_name }}</a>
                                <p class="mt-1 font-mono text-xs text-slate-500">{{ $row->livestock_code }}</p>
                            </div>
                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $statusStyles }}">{{ __(ucfirst($row->status)) }}</span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $lifecycleStyles }}">{{ str_replace('_', ' ', $row->lifecycle_status) }}</span>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase {{ $qualityStyles }}">{{ __('Quality :tier', ['tier' => $quality['tier']]) }}</span>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Type') }}</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $row->livestock_type ?: \App\Support\FarmerAnimalType::label((string) $row->type) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Breed') }}</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $row->breed ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Total count') }}</dt>
                                <dd class="mt-1 font-medium tabular-nums text-slate-900">{{ number_format((int) $row->total_count) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Animals recorded') }}</dt>
                                <dd class="mt-1 font-medium tabular-nums text-slate-900">{{ number_format((int) $row->animals_count) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Healthy / sick') }}</dt>
                                <dd class="mt-1 font-medium tabular-nums text-slate-900">
                                    <span class="text-emerald-800">{{ number_format((int) $row->healthy_quantity) }}</span>
                                    <span class="text-slate-400">/</span>
                                    <span class="text-red-800">{{ number_format((int) $row->sick_quantity) }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Health status') }}</dt>
                                <dd class="mt-1 font-medium capitalize text-slate-900">{{ str_replace('_', ' ', $row->health_status) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                            <a href="{{ route('farmer.farms.livestock.show', [$farm, $row]) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50">{{ __('View') }}</a>
                            <a href="{{ route('farmer.farms.livestock.animals.index', [$farm, $row]) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-bucha-primary transition hover:bg-slate-50">{{ __('Animals') }}</a>
                            <a href="{{ route('farmer.farms.livestock.edit', [$farm, $row]) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50">{{ __('Edit') }}</a>
                            <form method="post" action="{{ route('farmer.farms.livestock.destroy', [$farm, $row]) }}" class="inline-flex" onsubmit="return confirm('{{ __('Delete this livestock group?') }}');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-700 transition hover:bg-red-50">{{ __('Delete') }}</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
