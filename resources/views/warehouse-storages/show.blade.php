<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Cold Room storage') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if ($errors->has('delete'))
            <div class="rounded-bucha border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first('delete') }}</div>
        @endif

        @php
            $animal = $warehouseStorage->resolvedIntakeItem();
            $pmItem = $warehouseStorage->postMortemInspectionItem;
            $headerAnimal = $animal;
        @endphp

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">
                        @if ($headerAnimal?->ear_tag)
                            <span class="font-mono">{{ $headerAnimal->ear_tag }}</span>
                        @elseif ($warehouseStorage->batch?->batch_code)
                            {{ $warehouseStorage->batch->batch_code }}
                        @else
                            {{ __('Storage') }}
                        @endif
                    </p>
                    <p class="text-xs text-slate-500">{{ $warehouseStorage->warehouseFacility->facility_name ?? '—' }} · {{ ucfirst(str_replace('_', ' ', $warehouseStorage->status)) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('warehouse-storages.edit', $warehouseStorage) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <form method="post" action="{{ route('warehouse-storages.destroy', $warehouseStorage) }}" class="inline"
                          onsubmit="return confirm(@json(__('Delete this storage record? The animal will be available to store again.')));">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Delete') }}</button>
                    </form>
                    <a href="{{ route('batches.show', $warehouseStorage->batch) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('View batch') }}</a>
                    <a href="{{ route('warehouse-storages.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>

            @if ($animal)
                <div class="border-b border-slate-100 px-4 py-4">
                    <p class="mb-3 text-sm font-semibold text-slate-900">{{ __('Animal identification') }}</p>
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Ear tag') }}</dt>
                            <dd class="mt-0.5 font-mono text-sm font-semibold text-slate-900">{{ $animal->ear_tag ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Species') }}</dt>
                            <dd class="mt-0.5 text-sm text-slate-900">{{ $animal->species ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Sex') }}</dt>
                            <dd class="mt-0.5 text-sm text-slate-900">{{ $animal->sex ? ucfirst($animal->sex) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Live weight (before slaughter)') }}</dt>
                            <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $animal->live_weight_kg ? number_format((float) $animal->live_weight_kg, 2).' kg' : '—' }}</dd>
                        </div>
                        @if ($pmItem?->carcass_weight_kg)
                            <div>
                                <dt class="text-xs text-slate-500">{{ __('Carcass weight (post-mortem)') }}</dt>
                                <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) $pmItem->carcass_weight_kg, 2) }} kg</dd>
                            </div>
                        @endif
                        @if ($animal->age_months)
                            <div>
                                <dt class="text-xs text-slate-500">{{ __('Age') }}</dt>
                                <dd class="mt-0.5 text-sm text-slate-900">{{ $animal->age_months }} {{ __('months') }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Health at intake') }}</dt>
                            <dd class="mt-0.5 text-sm text-slate-900">{{ $animal->health_status ? ucfirst(str_replace('_', ' ', $animal->health_status)) : '—' }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Cold Room') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $warehouseStorage->warehouseFacility->facility_name ?? '' }}</dd>
                </div>
                @if ($warehouseStorage->coldRoom)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Linked cold room') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            {{ $warehouseStorage->coldRoom->name }}
                            @if ($warehouseStorage->coldRoom->standard)
                                <span class="text-slate-500">— {{ $warehouseStorage->coldRoom->standard->name }} ({{ $warehouseStorage->coldRoom->standard->min_temperature }}–{{ $warehouseStorage->coldRoom->standard->max_temperature }} °C)</span>
                            @else
                                <span class="text-xs text-amber-600">{{ __('No standard on room — monitoring inactive') }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Batch') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('batches.show', $warehouseStorage->batch) }}" class="text-bucha-primary hover:underline">{{ $warehouseStorage->batch->batch_code ?? '' }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Certificate') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        @if ($warehouseStorage->certificate)
                            <a href="{{ route('certificates.show', $warehouseStorage->certificate) }}" class="text-bucha-primary hover:underline">{{ $warehouseStorage->certificate->certificate_number ?: '#' . $warehouseStorage->certificate_id }}</a>
                        @else
                            <span class="text-slate-500">{{ __('Not issued yet') }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Entry date') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $warehouseStorage->entry_date->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Temperature at entry (°C)') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $warehouseStorage->temperature_at_entry !== null ? $warehouseStorage->temperature_at_entry : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Quantity stored') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) $warehouseStorage->quantity_stored, 2) }} {{ $warehouseStorage->quantity_unit_label }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst(str_replace('_', ' ', $warehouseStorage->status)) }}</dd>
                </div>
                @if ($warehouseStorage->released_date)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Released date') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $warehouseStorage->released_date->format('d M Y') }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Temperature logs') }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ __('Alert if temperature outside allowed range. Log readings for cold storage.') }}</p>
            </div>
            <div class="px-4 py-4">
                <form method="post" action="{{ route('warehouse-storages.temperature-logs.store', $warehouseStorage) }}" class="mb-5 space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <x-input-label for="recorded_temperature" :value="__('Temperature (°C)')" />
                            <x-text-input id="recorded_temperature" name="recorded_temperature" type="number" step="0.01" class="mt-1 block h-9 w-full" required />
                            <x-input-error class="mt-1" :messages="$errors->get('recorded_temperature')" />
                        </div>
                        <div>
                            <x-input-label for="recorded_at" :value="__('Recorded at')" />
                            <x-text-input id="recorded_at" name="recorded_at" type="datetime-local" class="mt-1 block h-9 w-full" :value="now()->format('Y-m-d\TH:i')" required />
                            <x-input-error class="mt-1" :messages="$errors->get('recorded_at')" />
                        </div>
                        <div>
                            <x-input-label for="recorded_by" :value="__('Recorded by')" />
                            <x-text-input id="recorded_by" name="recorded_by" type="text" class="mt-1 block h-9 w-full" :value="old('recorded_by', auth()->user()->name ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block h-9 w-full rounded-md border-slate-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" required>
                                @foreach (['normal' => __('Normal'), 'warning' => __('Warning'), 'critical' => __('Critical')] as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-1" :messages="$errors->get('status')" />
                        </div>
                    </div>
                    <button type="submit" class="inline-flex h-8 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Add log') }}</button>
                </form>

                @if ($warehouseStorage->temperatureLogs->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('No temperature logs yet.') }}</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($warehouseStorage->temperatureLogs->sortByDesc('recorded_at') as $log)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <div class="text-sm">
                                    <span class="font-medium tabular-nums">{{ $log->recorded_temperature }} °C</span>
                                    <span class="ml-2 text-slate-500">{{ $log->recorded_at->format('d M Y H:i') }}</span>
                                    @if ($log->recorded_by)
                                        <span class="text-slate-500"> · {{ $log->recorded_by }}</span>
                                    @endif
                                    <span class="ml-2 rounded px-2 py-0.5 text-xs {{ $log->status === 'critical' ? 'bg-red-50 text-red-800' : ($log->status === 'warning' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($log->status) }}</span>
                                </div>
                                <form method="post" action="{{ route('warehouse-storages.temperature-logs.destroy', [$warehouseStorage, $log]) }}" class="inline" onsubmit="return confirm('{{ __('Remove this log?') }}');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
