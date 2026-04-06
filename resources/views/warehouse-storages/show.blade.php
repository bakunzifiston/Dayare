<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('Storage') }} — {{ $warehouseStorage->batch->batch_code ?? '' }}
                </h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('warehouse-storages.edit', $warehouseStorage) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                <a href="{{ route('batches.show', $warehouseStorage->batch) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('View batch') }}</a>
                <a href="{{ route('warehouse-storages.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Back to list') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Cold Room') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $warehouseStorage->warehouseFacility->facility_name ?? '' }}</dd>
                    </div>
                    @if ($warehouseStorage->coldRoom)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">{{ __('Linked cold room') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $warehouseStorage->coldRoom->name }}
                                @if ($warehouseStorage->coldRoom->standard)
                                    <span class="text-slate-500">— {{ $warehouseStorage->coldRoom->standard->name }} ({{ $warehouseStorage->coldRoom->standard->min_temperature }}–{{ $warehouseStorage->coldRoom->standard->max_temperature }} °C)</span>
                                @else
                                    <span class="text-amber-600 text-xs">{{ __('No standard on room — monitoring inactive') }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Batch') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            <a href="{{ route('batches.show', $warehouseStorage->batch) }}" class="text-bucha-primary hover:underline">{{ $warehouseStorage->batch->batch_code ?? '' }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Certificate') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            <a href="{{ route('certificates.show', $warehouseStorage->certificate) }}" class="text-bucha-primary hover:underline">{{ $warehouseStorage->certificate->certificate_number ?: '#' . $warehouseStorage->certificate_id }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Entry date') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $warehouseStorage->entry_date->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Storage location') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $warehouseStorage->storage_location ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Temperature at entry (°C)') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $warehouseStorage->temperature_at_entry !== null ? $warehouseStorage->temperature_at_entry : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Quantity stored') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $warehouseStorage->quantity_stored }} {{ $warehouseStorage->quantity_unit_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ ucfirst(str_replace('_', ' ', $warehouseStorage->status)) }}</dd>
                    </div>
                    @if ($warehouseStorage->released_date)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">{{ __('Released date') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $warehouseStorage->released_date->format('d M Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h3 class="text-lg font-medium text-slate-900 mb-4">{{ __('Temperature logs') }}</h3>
                <p class="text-sm text-slate-500 mb-4">{{ __('Alert if temperature outside allowed range. Log readings for cold storage.') }}</p>

                <form method="post" action="{{ route('warehouse-storages.temperature-logs.store', $warehouseStorage) }}" class="mb-6 p-4 rounded-lg bg-slate-50 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <x-input-label for="recorded_temperature" :value="__('Temperature (°C)')" />
                            <x-text-input id="recorded_temperature" name="recorded_temperature" type="number" step="0.01" class="mt-1 block w-full" required />
                            <x-input-error class="mt-1" :messages="$errors->get('recorded_temperature')" />
                        </div>
                        <div>
                            <x-input-label for="recorded_at" :value="__('Recorded at')" />
                            <x-text-input id="recorded_at" name="recorded_at" type="datetime-local" class="mt-1 block w-full" :value="now()->format('Y-m-d\TH:i')" required />
                            <x-input-error class="mt-1" :messages="$errors->get('recorded_at')" />
                        </div>
                        <div>
                            <x-input-label for="recorded_by" :value="__('Recorded by')" />
                            <x-text-input id="recorded_by" name="recorded_by" type="text" class="mt-1 block w-full" :value="old('recorded_by', auth()->user()->name ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                                @foreach (['normal' => __('Normal'), 'warning' => __('Warning'), 'critical' => __('Critical')] as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-1" :messages="$errors->get('status')" />
                        </div>
                    </div>
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">{{ __('Add log') }}</button>
                </form>

                @if ($warehouseStorage->temperatureLogs->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('No temperature logs yet.') }}</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($warehouseStorage->temperatureLogs->sortByDesc('recorded_at') as $log)
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <span class="font-medium">{{ $log->recorded_temperature }} °C</span>
                                    <span class="text-slate-500 text-sm ml-2">{{ $log->recorded_at->format('d M Y H:i') }}</span>
                                    @if ($log->recorded_by)
                                        <span class="text-slate-500 text-sm"> · {{ $log->recorded_by }}</span>
                                    @endif
                                    <span class="ml-2 text-xs px-2 py-0.5 rounded {{ $log->status === 'critical' ? 'bg-red-100 text-red-800' : ($log->status === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($log->status) }}</span>
                                </div>
                                <form method="post" action="{{ route('warehouse-storages.temperature-logs.destroy', [$warehouseStorage, $log]) }}" class="inline" onsubmit="return confirm('{{ __('Remove this log?') }}');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
