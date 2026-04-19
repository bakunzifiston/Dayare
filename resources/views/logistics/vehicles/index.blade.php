@php
    $vehicleFieldKeys = ['plate_number', 'type', 'capacity_value', 'capacity_unit', 'vehicle_features'];
    $showVehicleForm = collect($vehicleFieldKeys)->contains(fn ($key) => old($key) !== null || $errors->has($key));
@endphp

@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    <div class="space-y-4">
        <form method="GET" action="{{ route('logistics.vehicles.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Company') }}</label>
            <div class="flex gap-2">
                <select name="company_id" class="w-full rounded-md border-slate-300 text-sm">
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === (int) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-[#334155] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1e293b]">{{ __('Load') }}</button>
            </div>
        </form>

        <section class="rounded-lg border border-slate-200 bg-white p-4" x-data="{ showVehicleForm: @js($showVehicleForm) }">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Vehicles') }}</h2>
                <button
                    type="button"
                    class="rounded-md bg-[#7A1C22] px-3 py-2 text-xs font-semibold text-white hover:bg-[#64161c]"
                    x-on:click="showVehicleForm = true; $nextTick(() => document.getElementById('vehicle-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                >
                    {{ __('Add vehicle') }}
                </button>
            </div>
            <form method="POST" action="{{ route('logistics.vehicles.store') }}" class="grid gap-2 md:grid-cols-2" id="vehicle-form" x-show="showVehicleForm" x-transition>
                @csrf
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                <input name="plate_number" value="{{ old('plate_number') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Vehicle number / plate') }}" required>
                <select name="type" class="rounded-md border-slate-300 text-sm" required>
                    <option value="">{{ __('Vehicle type') }}</option>
                    @foreach (\App\Models\LogisticsVehicle::TYPES as $type)
                        <option value="{{ $type }}" @selected(old('type') === $type)>{{ __($type) }}</option>
                    @endforeach
                </select>
                <input type="number" min="0.01" step="0.01" name="capacity_value" value="{{ old('capacity_value') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Capacity') }}" required>
                <select name="capacity_unit" class="rounded-md border-slate-300 text-sm" required>
                    <option value="">{{ __('Capacity unit') }}</option>
                    <option value="kg" @selected(old('capacity_unit') === 'kg')>{{ __('Kilograms (kg)') }}</option>
                    <option value="heads" @selected(old('capacity_unit') === 'heads')>{{ __('Heads (livestock)') }}</option>
                    <option value="tons" @selected(old('capacity_unit') === 'tons')>{{ __('Tons') }}</option>
                </select>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="vehicle_features[]" value="gps_tracking" @checked(in_array('gps_tracking', old('vehicle_features', [])))>
                    <span>{{ __('GPS tracking') }}</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="vehicle_features[]" value="temperature_control" @checked(in_array('temperature_control', old('vehicle_features', [])))>
                    <span>{{ __('Temperature control') }}</span>
                </label>
                <div class="md:col-span-2">
                    <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save vehicle') }}</button>
                </div>
            </form>
            <div class="mt-4">
                <x-logistics.table
                    :columns="[__('Plate'), __('Type'), __('Capacity'), __('Features'), __('Status')]"
                    :has-rows="$vehicles->isNotEmpty()"
                    :empty-message="__('No vehicles found')"
                >
                    @foreach ($vehicles as $vehicle)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $vehicle->plate_number }}</td>
                            <td class="px-4 py-3">{{ $vehicle->type }}</td>
                            <td class="px-4 py-3">{{ $vehicle->capacity_value !== null ? number_format((float) $vehicle->capacity_value, 2).' '.$vehicle->capacity_unit : '-' }}</td>
                            <td class="px-4 py-3">{{ collect($vehicle->vehicle_features ?? [])->map(fn ($f) => $f === 'gps_tracking' ? __('GPS tracking') : ($f === 'temperature_control' ? __('Temperature control') : $f))->implode(', ') ?: '-' }}</td>
                            <td class="px-4 py-3"><x-logistics.status-badge :status="$vehicle->status" /></td>
                        </tr>
                    @endforeach
                </x-logistics.table>
            </div>
        </section>
    </div>
@endcomponent
