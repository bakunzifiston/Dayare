@php $record = $record ?? null; @endphp
<div class="space-y-6">
    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            @include('farmer.health.partials.animal-select', ['animals' => $animals, 'selectedAnimalId' => $selectedAnimalId ?? $record?->animal_id, 'disabled' => $record !== null])
            <div><x-input-label for="disease_name" :value="__('Disease name')" /><x-text-input id="disease_name" name="disease_name" class="mt-1 block w-full" :value="old('disease_name', $record?->disease_name)" /></div>
            <div><x-input-label for="medicine_name" :value="__('Medicine name')" /><x-text-input id="medicine_name" name="medicine_name" class="mt-1 block w-full" :value="old('medicine_name', $record?->medicine_name)" /></div>
            <div><x-input-label for="dosage" :value="__('Dosage')" /><x-text-input id="dosage" name="dosage" class="mt-1 block w-full" :value="old('dosage', $record?->dosage)" /></div>
            <div><x-input-label for="treatment_method" :value="__('Treatment method')" /><x-text-input id="treatment_method" name="treatment_method" class="mt-1 block w-full" :value="old('treatment_method', $record?->treatment_method)" /></div>
            <div><x-input-label for="treatment_start_date" :value="__('Start date')" /><x-text-input id="treatment_start_date" name="treatment_start_date" type="date" class="mt-1 block w-full" :value="old('treatment_start_date', $record?->treatment_start_date?->format('Y-m-d'))" max="{{ date('Y-m-d') }}" required /></div>
            <div><x-input-label for="treatment_end_date" :value="__('End date')" /><x-text-input id="treatment_end_date" name="treatment_end_date" type="date" class="mt-1 block w-full" :value="old('treatment_end_date', $record?->treatment_end_date?->format('Y-m-d'))" /></div>
            <div><x-input-label for="follow_up_date" :value="__('Follow-up date')" /><x-text-input id="follow_up_date" name="follow_up_date" type="date" class="mt-1 block w-full" :value="old('follow_up_date', $record?->follow_up_date?->format('Y-m-d'))" /></div>
            <div><x-input-label for="status" :value="__('Status')" /><select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300" required>@foreach (\App\Models\Treatment::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $record?->status) === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>@endforeach</select></div>
            <div><x-input-label for="veterinarian_name" :value="__('Veterinarian name')" /><x-text-input id="veterinarian_name" name="veterinarian_name" class="mt-1 block w-full" :value="old('veterinarian_name', $record?->veterinarian_name)" /></div>
            <div class="sm:col-span-2"><x-input-label for="symptoms" :value="__('Symptoms')" /><textarea id="symptoms" name="symptoms" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('symptoms', $record?->symptoms) }}</textarea></div>
            <div class="sm:col-span-2"><x-input-label for="diagnosis" :value="__('Diagnosis')" /><textarea id="diagnosis" name="diagnosis" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('diagnosis', $record?->diagnosis) }}</textarea></div>
            <div class="sm:col-span-2"><x-input-label for="notes" :value="__('Notes')" /><textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $record?->notes) }}</textarea></div>
            <div class="sm:col-span-2"><x-input-label for="attachment" :value="__('Attachment')" /><input id="attachment" name="attachment" type="file" accept=".pdf,image/*" class="mt-1 block w-full text-sm text-slate-600"></div>
        </div>
    </section>
</div>
