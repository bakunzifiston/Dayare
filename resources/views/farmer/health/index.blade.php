<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('farmer.farms.show', $farm) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← :farm', ['farm' => $farm->name]) }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Farm health') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Counts are for supply decisions. Visit logs are history only.') }}</p>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-10">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        {{-- 1. PRIMARY: health counts (authoritative) --}}
        <section aria-labelledby="health-counts-heading">
            <h3 id="health-counts-heading" class="text-sm font-semibold text-slate-900 mb-2">{{ __('Health counts') }}</h3>
            <div class="rounded-bucha border border-slate-200/80 bg-white px-4 py-3 text-sm shadow-sm">
                <div class="flex flex-wrap gap-x-8 gap-y-1">
                    <span><span class="text-slate-500">{{ __('Total healthy') }}</span> <strong class="text-emerald-800 tabular-nums text-base">{{ $healthHeadcounts['healthy'] }}</strong></span>
                    <span><span class="text-slate-500">{{ __('Total sick') }}</span> <strong class="text-red-800 tabular-nums text-base">{{ $healthHeadcounts['sick'] }}</strong></span>
                    @if ($healthHeadcounts['unrecorded'] > 0)
                        <span><span class="text-slate-500">{{ __('Unassigned') }}</span> <strong class="text-amber-800 tabular-nums">{{ $healthHeadcounts['unrecorded'] }}</strong></span>
                    @endif
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ __('Unassigned means healthy + sick is below total for some rows. Fix splits in the table below.') }}</p>
            </div>
        </section>

        {{-- Herd health table --}}
        @if ($farm->livestock->isEmpty())
            <p class="text-sm text-slate-600">{{ __('Add livestock rows first, then set healthy vs sick quantities here.') }}</p>
        @else
            <section aria-labelledby="herd-table-heading">
                <h3 id="herd-table-heading" class="text-sm font-semibold text-slate-900 mb-2">{{ __('Herd health (by livestock row)') }}</h3>
                <form method="post" action="{{ route('farmer.farms.livestock-health-splits.update', $farm) }}" class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-4">
                    @csrf
                    @method('patch')
                    <p class="text-sm text-slate-600">{{ __('Healthy + sick must equal total quantity for each row. Supply uses healthy quantity only.') }}</p>
                    <div class="overflow-x-auto rounded-lg border border-slate-200/80">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th class="px-4 py-2">{{ __('Livestock') }}</th>
                                    <th class="px-4 py-2">{{ __('Total') }}</th>
                                    <th class="px-4 py-2">{{ __('Healthy') }}</th>
                                    <th class="px-4 py-2">{{ __('Sick') }}</th>
                                    <th class="px-4 py-2">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($farm->livestock as $l)
                                    <tr>
                                        <td class="px-4 py-2 capitalize">{{ $l->type }}@if ($l->breed !== '') <span class="text-slate-500">— {{ $l->breed }}</span>@endif</td>
                                        <td class="px-4 py-2 tabular-nums">{{ $l->total_quantity }}</td>
                                        <td class="px-4 py-2">
                                            <input type="number" name="splits[{{ $l->id }}][healthy]" min="0" required
                                                value="{{ old('splits.'.$l->id.'.healthy', $l->healthy_quantity) }}"
                                                class="w-24 rounded-lg border-gray-300 text-sm" />
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" name="splits[{{ $l->id }}][sick]" min="0" required
                                                value="{{ old('splits.'.$l->id.'.sick', $l->sick_quantity) }}"
                                                class="w-24 rounded-lg border-gray-300 text-sm" />
                                            @error('splits.'.$l->id)
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium capitalize
                                                @if($l->herd_health_status === \App\Models\Livestock::HERD_STATUS_HEALTHY) bg-emerald-100 text-emerald-900
                                                @elseif($l->herd_health_status === \App\Models\Livestock::HERD_STATUS_SICK) bg-red-100 text-red-900
                                                @else bg-amber-100 text-amber-900 @endif">
                                                {{ $l->herd_health_status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('splits')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Save herd health counts') }}</button>
                </form>
            </section>
        @endif

        {{-- 2. SECONDARY: visit logs (history only) --}}
        <section aria-labelledby="health-logs-heading" class="border-t border-slate-200 pt-8">
            <h3 id="health-logs-heading" class="text-sm font-semibold text-slate-900 mb-1">{{ __('Health visit log') }}</h3>
            <p class="text-sm text-slate-500 mb-4">{{ __('Optional history. These entries do not change healthy or sick quantities.') }}</p>

            <form method="post" action="{{ route('farmer.farms.health-records.store', $farm) }}" class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-4 mb-8">
                @csrf
                <h4 class="font-medium text-slate-800">{{ __('Add log entry') }}</h4>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="record_date" :value="__('Date')" />
                        <x-text-input id="record_date" name="record_date" type="date" class="mt-1 block w-full" :value="old('record_date', now()->toDateString())" required />
                        <x-input-error :messages="$errors->get('record_date')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="condition" :value="__('Condition')" />
                        <select name="condition" id="condition" class="mt-1 block w-full rounded-lg border-gray-300" required>
                            @foreach (\App\Models\AnimalHealthRecord::CONDITIONS as $c)
                                <option value="{{ $c }}" @selected(old('condition') === $c)>{{ ucfirst($c) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="livestock_id" :value="__('Livestock row (optional)')" />
                        <select name="livestock_id" id="livestock_id" class="mt-1 block w-full rounded-lg border-gray-300">
                            <option value="">{{ __('—') }}</option>
                            @foreach ($farm->livestock as $l)
                                <option value="{{ $l->id }}" @selected(old('livestock_id') == $l->id)>{{ ucfirst($l->type) }} (#{{ $l->id }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('livestock_id')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-800 text-white text-sm font-semibold rounded-bucha">{{ __('Save log entry') }}</button>
            </form>

            <h4 class="text-sm font-medium text-slate-800 mb-2">{{ __('History') }}</h4>
            <div class="bg-white rounded-bucha border border-slate-200/60 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-2">{{ __('Date') }}</th>
                            <th class="px-4 py-2">{{ __('Condition') }}</th>
                            <th class="px-4 py-2">{{ __('Livestock') }}</th>
                            <th class="px-4 py-2">{{ __('Notes') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($records as $r)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $r->record_date?->toDateString() }}</td>
                                <td class="px-4 py-2 capitalize">{{ $r->condition }}</td>
                                <td class="px-4 py-2 text-slate-600">
                                    @if ($r->livestock_id)
                                        <span class="capitalize">{{ $r->livestock?->type ?? '—' }}</span>
                                        <span class="text-slate-400">#{{ $r->livestock_id }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-slate-600">{{ \Illuminate\Support\Str::limit($r->notes ?? '', 80) }}</td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <form action="{{ route('farmer.farms.health-records.destroy', [$farm, $r]) }}" method="post" class="inline" onsubmit="return confirm('{{ __('Delete?') }}');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ __('No log entries yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $records->links() }}</div>
        </section>
    </div>
</x-app-layout>
