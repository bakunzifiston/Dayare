@php
    $livestock = $livestock ?? null;
@endphp

<div class="space-y-6">
    <section class="rounded-bucha border border-slate-200/60 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Group identity') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="livestock_name" :value="__('Livestock name')" />
                <x-text-input id="livestock_name" name="livestock_name" type="text" class="mt-1 block w-full" :value="old('livestock_name', $livestock?->livestock_name)" required />
                <x-input-error :messages="$errors->get('livestock_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="livestock_type" :value="__('Livestock type')" />
                <x-text-input id="livestock_type" name="livestock_type" type="text" class="mt-1 block w-full" :value="old('livestock_type', $livestock?->livestock_type)" list="livestock-type-suggestions" required />
                <datalist id="livestock-type-suggestions">
                    @foreach ($types as $type)
                        <option value="{{ $type }}">{{ \App\Support\FarmerAnimalType::label($type) }}</option>
                    @endforeach
                </datalist>
                <x-input-error :messages="$errors->get('livestock_type')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="production_purpose" :value="__('Production purpose')" />
                <x-text-input id="production_purpose" name="production_purpose" type="text" class="mt-1 block w-full" :value="old('production_purpose', $livestock?->production_purpose)" placeholder="{{ __('Dairy, beef, layers, broilers, breeding, etc.') }}" required />
                <x-input-error :messages="$errors->get('production_purpose')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200/60 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Herd counts') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['total_count', __('Total count')], ['male_count', __('Male count')], ['female_count', __('Female count')], ['young_count', __('Young count')]] as [$field, $label])
                <div>
                    <x-input-label :for="$field" :value="$label" />
                    <x-text-input :id="$field" :name="$field" type="number" min="0" class="mt-1 block w-full" :value="old($field, $livestock?->$field ?? 0)" required />
                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200/60 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Management') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="farming_method" :value="__('Farming method')" />
                <x-text-input id="farming_method" name="farming_method" type="text" class="mt-1 block w-full" :value="old('farming_method', $livestock?->farming_method)" required />
                <x-input-error :messages="$errors->get('farming_method')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="feeding_method" :value="__('Feeding method')" />
                <select id="feeding_method" name="feeding_method" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    <option value="">{{ __('Select feeding method') }}</option>
                    @foreach (\App\Models\Livestock::FEEDING_TYPES as $method)
                        <option value="{{ $method }}" @selected(old('feeding_method', $livestock?->feeding_method) === $method)>{{ __(ucfirst($method)) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('feeding_method')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="water_source" :value="__('Water source')" />
                <x-text-input id="water_source" name="water_source" type="text" class="mt-1 block w-full" :value="old('water_source', $livestock?->water_source)" required />
                <x-input-error :messages="$errors->get('water_source')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="housing_location" :value="__('Housing location')" />
                <x-text-input id="housing_location" name="housing_location" type="text" class="mt-1 block w-full" :value="old('housing_location', $livestock?->housing_location)" />
                <x-input-error :messages="$errors->get('housing_location')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="acquisition_date" :value="__('Acquisition date')" />
                <x-text-input id="acquisition_date" name="acquisition_date" type="date" class="mt-1 block w-full" :value="old('acquisition_date', $livestock?->acquisition_date?->format('Y-m-d'))" max="{{ date('Y-m-d') }}" required />
                <x-input-error :messages="$errors->get('acquisition_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="acquisition_source" :value="__('Acquisition source')" />
                <x-text-input id="acquisition_source" name="acquisition_source" type="text" class="mt-1 block w-full" :value="old('acquisition_source', $livestock?->acquisition_source)" required />
                <x-input-error :messages="$errors->get('acquisition_source')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200/60 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Status') }}</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="health_status" :value="__('Health status')" />
                <select id="health_status" name="health_status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Livestock::HEALTH_STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('health_status', $livestock?->health_status ?? \App\Models\Livestock::HEALTH_HEALTHY) === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('health_status')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="lifecycle_status" :value="__('Lifecycle status')" />
                <select id="lifecycle_status" name="lifecycle_status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Livestock::LIFECYCLE_STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('lifecycle_status', $livestock?->lifecycle_status ?? \App\Models\Livestock::LIFECYCLE_ACTIVE) === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('lifecycle_status')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Livestock::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('status', $livestock?->status ?? \App\Models\Livestock::STATUS_ACTIVE) === $status)>{{ __(ucfirst($status)) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>
        </div>
        <div>
            <x-input-label for="notes" :value="__('Notes')" />
            <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('notes', $livestock?->notes) }}</textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>
    </section>
</div>
