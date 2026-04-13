<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <a href="{{ route('farmer.farms.show', $farm) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← :farm', ['farm' => $farm->name]) }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Livestock') }}</h2>
            </div>
            <a href="{{ route('farmer.farms.livestock.create', $farm) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-xs font-semibold uppercase tracking-widest rounded-bucha">{{ __('Add') }}</a>
        </div>
    </x-slot>

    <div
        class="max-w-5xl space-y-4"
        x-data="{
            modalOpen: false,
            loading: false,
            err: null,
            payload: null,
            async openDetails(url) {
                this.modalOpen = true;
                this.loading = true;
                this.err = null;
                this.payload = null;
                try {
                    const r = await fetch(url, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!r.ok) throw new Error();
                    this.payload = await r.json();
                } catch (e) {
                    this.err = '{{ __('Could not load details.') }}';
                }
                this.loading = false;
            },
            tierClass(t) {
                if (t === 'A') return 'bg-emerald-100 text-emerald-900';
                if (t === 'B') return 'bg-amber-100 text-amber-900';
                return 'bg-slate-200 text-slate-800';
            },
        }"
        @keydown.escape.window="modalOpen = false"
    >
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @if ($livestock->isEmpty())
            <p class="text-sm text-slate-600">{{ __('No livestock rows yet.') }}</p>
        @else
            <div class="rounded-bucha border border-slate-200/80 bg-white px-4 py-3 text-sm shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Farm health counts (healthy vs sick)') }}</p>
                <div class="flex flex-wrap gap-x-6 gap-y-1">
                    <span><span class="text-slate-500">{{ __('Healthy') }}</span> <strong class="text-emerald-800 tabular-nums">{{ $healthHeadcounts['healthy'] }}</strong></span>
                    <span><span class="text-slate-500">{{ __('Sick') }}</span> <strong class="text-red-800 tabular-nums">{{ $healthHeadcounts['sick'] }}</strong></span>
                    @if ($healthHeadcounts['unrecorded'] > 0)
                        <span><span class="text-slate-500">{{ __('Unassigned') }}</span> <strong class="text-amber-800 tabular-nums">{{ $healthHeadcounts['unrecorded'] }}</strong></span>
                    @endif
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ __('Set healthy vs sick on the farm’s Health page. Unassigned means healthy + sick is less than total for that row.') }} <a href="{{ route('farmer.farms.health-records.index', $farm) }}" class="text-bucha-primary hover:underline">{{ __('Health') }}</a></p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($livestock as $row)
                    @php
                        $qs = $row->qualityScore();
                    @endphp
                    <div class="rounded-bucha border border-slate-200/60 bg-white p-4 shadow-sm flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-slate-900 capitalize">{{ $row->type }}</p>
                                @if ($row->breed !== '')
                                    <p class="text-sm text-slate-600">{{ $row->breed }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 text-xs font-bold uppercase tracking-wide rounded px-2 py-1 {{ $qs['tier'] === 'A' ? 'bg-emerald-100 text-emerald-900' : ($qs['tier'] === 'B' ? 'bg-amber-100 text-amber-900' : 'bg-slate-200 text-slate-800') }}" title="{{ __('Quality score') }}">{{ $qs['tier'] }}</span>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                            <dt class="text-slate-500">{{ __('Available') }}</dt>
                            <dd class="text-slate-900 text-right">{{ $row->available_quantity }} / {{ $row->total_quantity }}</dd>
                            @if ($row->feeding_type)
                                <dt class="text-slate-500">{{ __('Feeding') }}</dt>
                                <dd class="text-slate-900 text-right capitalize">{{ str_replace('_', ' ', $row->feeding_type) }}</dd>
                            @endif
                            @if ($row->health_status)
                                <dt class="text-slate-500">{{ __('Health') }}</dt>
                                <dd class="text-slate-900 text-right capitalize">{{ $row->health_status }}</dd>
                            @endif
                            @if ($row->base_price !== null)
                                <dt class="text-slate-500">{{ __('Base price') }}</dt>
                                <dd class="text-slate-900 text-right">{{ number_format((float) $row->base_price, 2) }}</dd>
                            @endif
                        </dl>
                        @php
                            $hh = (int) $row->healthy_quantity;
                            $hs = (int) $row->sick_quantity;
                            $splitSum = $hh + $hs;
                            $headN = (int) $row->total_quantity;
                        @endphp
                        <div class="rounded-md border border-slate-100 bg-slate-50/90 px-3 py-2 text-xs space-y-1">
                            <p class="font-medium text-slate-700">{{ __('Health quantities') }}</p>
                            <p>
                                <span class="text-emerald-800 font-medium">{{ __('Healthy') }}: {{ $hh }}</span>
                                <span class="text-slate-400 mx-1">·</span>
                                <span class="text-red-800 font-medium">{{ __('Sick') }}: {{ $hs }}</span>
                                <span class="text-slate-500"> · {{ __('Status') }}: <span class="capitalize font-medium text-slate-800">{{ $row->herd_health_status }}</span></span>
                            </p>
                            @if ($splitSum !== $headN)
                                <p class="text-amber-800">{{ __('Healthy + sick (:sum) does not match total (:total). Fix on the Health page.', ['sum' => $splitSum, 'total' => $headN]) }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2 mt-auto pt-1 border-t border-slate-100">
                            <button
                                type="button"
                                class="text-sm font-medium text-bucha-primary hover:underline"
                                @click="openDetails('{{ route('farmer.farms.livestock.show', [$farm, $row]) }}')"
                            >{{ __('View details') }}</button>
                            <a href="{{ route('farmer.farms.livestock.edit', [$farm, $row]) }}" class="text-sm text-slate-600 hover:underline">{{ __('Edit') }}</a>
                            <form action="{{ route('farmer.farms.livestock.destroy', [$farm, $row]) }}" method="post" class="inline ml-auto" onsubmit="return confirm('{{ __('Remove?') }}');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('Delete') }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <section class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Move livestock between farms') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ __('A valid RAB movement permit is required.') }}</p>
            <x-input-error :messages="$errors->get('destination_farm_id')" class="mt-2" />
            <x-input-error :messages="$errors->get('livestock_id')" class="mt-2" />
            <x-input-error :messages="$errors->get('movement_permit_id')" class="mt-2" />
            <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
            <form method="post" action="{{ route('farmer.farms.livestock.move', $farm) }}" class="mt-3 grid sm:grid-cols-3 gap-3">
                @csrf
                <input type="hidden" name="source_farm_id" value="{{ $farm->id }}">
                <div>
                    <x-input-label for="destination_farm_id" :value="__('Destination farm')" />
                    <select id="destination_farm_id" name="destination_farm_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        <option value="">{{ __('Select destination') }}</option>
                        @foreach ($destinationFarms as $destinationFarm)
                            <option value="{{ $destinationFarm->id }}">{{ $destinationFarm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="livestock_id" :value="__('Livestock row')" />
                    <select id="livestock_id" name="livestock_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        <option value="">{{ __('Select livestock') }}</option>
                        @foreach ($livestock as $row)
                            <option value="{{ $row->id }}">{{ ucfirst($row->type) }} #{{ $row->id }} ({{ __('Available') }}: {{ $row->available_quantity }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="quantity" :value="__('Quantity')" />
                    <x-text-input id="quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" :value="old('quantity', 1)" required />
                </div>
                <div>
                    <x-input-label for="reason" :value="__('Reason')" />
                    <select id="reason" name="reason" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        <option value="transfer">{{ __('Transfer') }}</option>
                        <option value="sale">{{ __('Sale') }}</option>
                        <option value="loss">{{ __('Loss') }}</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="movement_date" :value="__('Movement date')" />
                    <x-text-input id="movement_date" name="movement_date" type="date" class="mt-1 block w-full" :value="old('movement_date', now()->toDateString())" required />
                </div>
                <div>
                    <x-input-label for="movement_permit_id" :value="__('Movement permit')" />
                    <select id="movement_permit_id" name="movement_permit_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        <option value="">{{ __('Select permit') }}</option>
                        @foreach ($validPermits as $permit)
                            <option value="{{ $permit->id }}">{{ $permit->permit_number }} ({{ __('Valid until') }} {{ $permit->expiry_date?->toDateString() }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Record movement') }}</button>
                    @if ($validPermits->isEmpty())
                        <p class="mt-2 text-xs text-amber-700">{{ __('No valid movement permit available for this farm. Upload one first.') }}</p>
                    @endif
                </div>
            </form>
        </section>

        <div
            x-show="modalOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 flex items-end sm:items-center justify-center bg-slate-900/40 p-4"
            style="display: none;"
            @click.self="modalOpen = false"
        >
            <div
                class="relative z-50 w-full max-w-lg max-h-[85vh] overflow-y-auto rounded-bucha bg-white shadow-xl border border-slate-200/80 p-5"
                @click.stop
            >
                <div class="flex items-start justify-between gap-2 mb-4">
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('Livestock details') }}</h3>
                    <button type="button" class="text-slate-500 hover:text-slate-800 text-xl leading-none" @click="modalOpen = false" aria-label="{{ __('Close') }}">&times;</button>
                </div>
                <template x-if="loading">
                    <p class="text-sm text-slate-600">{{ __('Loading…') }}</p>
                </template>
                <template x-if="err">
                    <p class="text-sm text-red-600" x-text="err"></p>
                </template>
                <template x-if="payload && !loading">
                    <div class="space-y-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500">{{ __('Quality') }}</span>
                            <span class="text-xs font-bold uppercase rounded px-2 py-0.5" :class="tierClass(payload.quality.tier)" x-text="payload.quality.tier"></span>
                            <span class="text-slate-500" x-text="`${payload.quality.points}/${payload.quality.max}`"></span>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Core') }}</p>
                            <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                                <dt class="text-slate-500">{{ __('Type') }}</dt>
                                <dd class="text-slate-900 capitalize" x-text="payload.core.type"></dd>
                                <dt class="text-slate-500">{{ __('Breed') }}</dt>
                                <dd class="text-slate-900" x-text="payload.core.breed || '—'"></dd>
                                <dt class="text-slate-500">{{ __('Feeding') }}</dt>
                                <dd class="text-slate-900 capitalize" x-text="payload.core.feeding_type ? payload.core.feeding_type.replace('_', ' ') : '—'"></dd>
                                <dt class="text-slate-500">{{ __('Total') }}</dt>
                                <dd class="text-slate-900" x-text="payload.core.total_quantity"></dd>
                                <dt class="text-slate-500">{{ __('Available') }}</dt>
                                <dd class="text-slate-900" x-text="payload.core.available_quantity"></dd>
                                <dt class="text-slate-500">{{ __('Base price') }}</dt>
                                <dd class="text-slate-900" x-text="payload.core.base_price != null ? Number(payload.core.base_price).toFixed(2) : '—'"></dd>
                                <dt class="text-slate-500">{{ __('Health') }}</dt>
                                <dd class="text-slate-900 capitalize" x-text="payload.core.health_status || '—'"></dd>
                                <dt class="text-slate-500">{{ __('Healthy quantity') }}</dt>
                                <dd class="text-slate-900 tabular-nums" x-text="payload.core.healthy_quantity"></dd>
                                <dt class="text-slate-500">{{ __('Sick quantity') }}</dt>
                                <dd class="text-slate-900 tabular-nums" x-text="payload.core.sick_quantity"></dd>
                                <dt class="text-slate-500">{{ __('Herd status') }}</dt>
                                <dd class="text-slate-900 capitalize" x-text="payload.core.herd_health_status"></dd>
                            </dl>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Extended') }}</p>
                            <template x-if="!payload.extended">
                                <p class="text-slate-600">{{ __('No extended details recorded.') }}</p>
                            </template>
                            <template x-if="payload.extended">
                                <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                                    <dt class="text-slate-500">{{ __('Age range') }}</dt>
                                    <dd class="text-slate-900 whitespace-pre-wrap" x-text="payload.extended.age_range || '—'"></dd>
                                    <dt class="text-slate-500">{{ __('Weight range') }}</dt>
                                    <dd class="text-slate-900 whitespace-pre-wrap" x-text="payload.extended.weight_range || '—'"></dd>
                                    <dt class="text-slate-500">{{ __('Notes') }}</dt>
                                    <dd class="text-slate-900 whitespace-pre-wrap" x-text="payload.extended.notes || '—'"></dd>
                                </dl>
                            </template>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Health visit log (history only)') }}</p>
                            <p class="text-xs text-slate-500 mb-2" x-show="payload.health_log && payload.health_log.note" x-text="payload.health_log && payload.health_log.note"></p>
                            <template x-if="!payload.health_log || !payload.health_log.latest">
                                <p class="text-slate-600">{{ __('No visit log for this row yet.') }}</p>
                            </template>
                            <template x-if="payload.health_log && payload.health_log.latest">
                                <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                                    <dt class="text-slate-500">{{ __('Condition') }}</dt>
                                    <dd class="text-slate-900 capitalize" x-text="payload.health_log.latest.condition"></dd>
                                    <dt class="text-slate-500">{{ __('Date') }}</dt>
                                    <dd class="text-slate-900" x-text="payload.health_log.latest.record_date || '—'"></dd>
                                    <dt class="text-slate-500">{{ __('Notes') }}</dt>
                                    <dd class="text-slate-900 whitespace-pre-wrap" x-text="payload.health_log.latest.notes || '—'"></dd>
                                </dl>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
