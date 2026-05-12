@php
    $record = $record ?? null;
@endphp

<div class="space-y-6">
    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Animal and vaccine') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            @include('farmer.health.partials.animal-select', [
                'animals' => $animals,
                'selectedAnimalId' => $selectedAnimalId ?? $record?->animal_id,
                'disabled' => $record !== null,
            ])
            <div>
                <x-input-label for="vaccine_name" :value="__('Vaccine name')" />
                <x-text-input id="vaccine_name" name="vaccine_name" type="text" class="mt-1 block w-full" :value="old('vaccine_name', $record?->vaccine_name)" required />
            </div>
            <div>
                <x-input-label for="vaccine_type" :value="__('Vaccine type')" />
                <x-text-input id="vaccine_type" name="vaccine_type" type="text" class="mt-1 block w-full" :value="old('vaccine_type', $record?->vaccine_type)" />
            </div>
            <div>
                <x-input-label for="manufacturer" :value="__('Manufacturer')" />
                <x-text-input id="manufacturer" name="manufacturer" type="text" class="mt-1 block w-full" :value="old('manufacturer', $record?->manufacturer)" />
            </div>
            <div>
                <x-input-label for="batch_number" :value="__('Batch number')" />
                <x-text-input id="batch_number" name="batch_number" type="text" class="mt-1 block w-full" :value="old('batch_number', $record?->batch_number)" />
            </div>
            <div>
                <x-input-label for="dosage" :value="__('Dosage')" />
                <x-text-input id="dosage" name="dosage" type="text" class="mt-1 block w-full" :value="old('dosage', $record?->dosage)" />
            </div>
            <div>
                <x-input-label for="administration_method" :value="__('Administration method')" />
                <x-text-input id="administration_method" name="administration_method" type="text" class="mt-1 block w-full" :value="old('administration_method', $record?->administration_method)" />
            </div>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Schedule and provider') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="vaccination_date" :value="__('Vaccination date')" />
                <x-text-input id="vaccination_date" name="vaccination_date" type="date" class="mt-1 block w-full" :value="old('vaccination_date', $record?->vaccination_date?->format('Y-m-d'))" max="{{ date('Y-m-d') }}" required />
            </div>
            <div>
                <x-input-label for="next_due_date" :value="__('Next due date')" />
                <x-text-input id="next_due_date" name="next_due_date" type="date" class="mt-1 block w-full" :value="old('next_due_date', $record?->next_due_date?->format('Y-m-d'))" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Vaccination::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('status', $record?->status) === $status)>{{ __(ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="veterinarian_name" :value="__('Veterinarian name')" />
                <x-text-input id="veterinarian_name" name="veterinarian_name" type="text" class="mt-1 block w-full" :value="old('veterinarian_name', $record?->veterinarian_name)" />
            </div>
            <div>
                <x-input-label for="veterinary_clinic" :value="__('Veterinary clinic')" />
                <x-text-input id="veterinary_clinic" name="veterinary_clinic" type="text" class="mt-1 block w-full" :value="old('veterinary_clinic', $record?->veterinary_clinic)" />
            </div>
            <div>
                <x-input-label for="administered_by" :value="__('Administered by')" />
                <x-text-input id="administered_by" name="administered_by" type="text" class="mt-1 block w-full" :value="old('administered_by', $record?->administered_by)" />
            </div>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Observations and files') }}</h3>
        <div class="grid gap-4">
            <div>
                <x-input-label for="side_effects" :value="__('Side effects')" />
                <textarea id="side_effects" name="side_effects" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('side_effects', $record?->side_effects) }}</textarea>
            </div>
            <div>
                <x-input-label for="reaction_notes" :value="__('Reaction notes')" />
                <textarea id="reaction_notes" name="reaction_notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('reaction_notes', $record?->reaction_notes) }}</textarea>
            </div>
            <div>
                <x-input-label for="notes" :value="__('Notes')" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $record?->notes) }}</textarea>
            </div>
            <div>
                <x-input-label for="attachment" :value="__('Attachment')" />
                <input id="attachment" name="attachment" type="file" accept=".pdf,image/*" class="mt-1 block w-full text-sm text-slate-600">
            </div>
        </div>
    </section>
</div>
