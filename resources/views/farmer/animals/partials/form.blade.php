@php
    $animal = $animal ?? null;
@endphp

<div class="space-y-6">
    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Identity') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="animal_name" :value="__('Animal name')" />
                <x-text-input id="animal_name" name="animal_name" type="text" class="mt-1 block w-full" :value="old('animal_name', $animal?->animal_name)" />
            </div>
            <div>
                <x-input-label for="tag_number" :value="__('Tag number')" />
                <x-text-input id="tag_number" name="tag_number" type="text" class="mt-1 block w-full" :value="old('tag_number', $animal?->tag_number)" required />
                <p class="mt-1 text-xs text-slate-500">{{ __('Unique within this livestock group. Used when selecting the animal elsewhere in the workspace.') }}</p>
            </div>
            <div>
                <x-input-label for="gender" :value="__('Gender')" />
                <select id="gender" name="gender" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Animal::GENDERS as $gender)
                        <option value="{{ $gender }}" @selected(old('gender', $animal?->gender) === $gender)>{{ __(ucfirst($gender)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="photo" :value="__('Photo')" />
                <input id="photo" name="photo" type="file" accept="image/*" class="mt-1 block w-full text-sm text-slate-600">
            </div>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Physical profile') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="birth_date" :value="__('Birth date')" />
                <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', $animal?->birth_date?->format('Y-m-d'))" max="{{ date('Y-m-d') }}" />
            </div>
            <div>
                <x-input-label for="age" :value="__('Age')" />
                <x-text-input id="age" name="age" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('age', $animal?->age)" />
            </div>
            <div>
                <x-input-label for="weight" :value="__('Weight (kg)')" />
                <x-text-input id="weight" name="weight" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('weight', $animal?->weight)" />
            </div>
            <div>
                <x-input-label for="color_markings" :value="__('Color / markings')" />
                <x-text-input id="color_markings" name="color_markings" type="text" class="mt-1 block w-full" :value="old('color_markings', $animal?->color_markings)" />
            </div>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Acquisition and lineage') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="acquisition_type" :value="__('Acquisition type')" />
                <select id="acquisition_type" name="acquisition_type" class="mt-1 block w-full rounded-lg border-gray-300">
                    <option value="">{{ __('Not set') }}</option>
                    @if ($animal?->acquisition_type && ! in_array($animal->acquisition_type, \App\Models\Animal::ACQUISITION_TYPES, true))
                        <option value="{{ $animal->acquisition_type }}" @selected(old('acquisition_type', $animal->acquisition_type) === $animal->acquisition_type)>{{ \App\Models\Animal::acquisitionTypeLabel($animal->acquisition_type) }}</option>
                    @endif
                    @foreach (\App\Models\Animal::ACQUISITION_TYPES as $type)
                        <option value="{{ $type }}" @selected(old('acquisition_type', $animal?->acquisition_type) === $type)>{{ \App\Models\Animal::acquisitionTypeLabel($type) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('acquisition_type')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="acquisition_date" :value="__('Acquisition date')" />
                <x-text-input id="acquisition_date" name="acquisition_date" type="date" class="mt-1 block w-full" :value="old('acquisition_date', $animal?->acquisition_date?->format('Y-m-d'))" max="{{ date('Y-m-d') }}" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="source" :value="__('Source')" />
                <x-text-input id="source" name="source" type="text" class="mt-1 block w-full" :value="old('source', $animal?->source)" />
            </div>
            <div>
                <x-input-label for="mother_tag" :value="__('Mother tag')" />
                <x-text-input id="mother_tag" name="mother_tag" type="text" class="mt-1 block w-full" :value="old('mother_tag', $animal?->mother_tag)" />
            </div>
            <div>
                <x-input-label for="father_tag" :value="__('Father tag')" />
                <x-text-input id="father_tag" name="father_tag" type="text" class="mt-1 block w-full" :value="old('father_tag', $animal?->father_tag)" />
            </div>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Status') }}</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="health_status" :value="__('Health status')" />
                <select id="health_status" name="health_status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Animal::HEALTH_STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('health_status', $animal?->health_status ?? \App\Models\Animal::HEALTH_HEALTHY) === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="production_status" :value="__('Production status')" />
                <select id="production_status" name="production_status" class="mt-1 block w-full rounded-lg border-gray-300">
                    <option value="">{{ __('Not set') }}</option>
                    @foreach (\App\Models\Animal::PRODUCTION_STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('production_status', $animal?->production_status) === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="lifecycle_status" :value="__('Lifecycle status')" />
                <select id="lifecycle_status" name="lifecycle_status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Animal::LIFECYCLE_STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('lifecycle_status', $animal?->lifecycle_status ?? \App\Models\Animal::LIFECYCLE_ACTIVE) === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <x-input-label for="current_condition" :value="__('Current condition')" />
            <select id="current_condition" name="current_condition" class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="">{{ __('Not set') }}</option>
                @if ($animal?->current_condition && ! in_array($animal->current_condition, \App\Models\Animal::CURRENT_CONDITIONS, true))
                    <option value="{{ $animal->current_condition }}" @selected(old('current_condition', $animal->current_condition) === $animal->current_condition)>{{ $animal->current_condition }} ({{ __('legacy') }})</option>
                @endif
                @foreach (\App\Models\Animal::CURRENT_CONDITIONS as $condition)
                    <option value="{{ $condition }}" @selected(old('current_condition', $animal?->current_condition) === $condition)>{{ \App\Models\Animal::currentConditionLabel($condition) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('current_condition')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="notes" :value="__('Notes')" />
            <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('notes', $animal?->notes) }}</textarea>
        </div>
    </section>
</div>
