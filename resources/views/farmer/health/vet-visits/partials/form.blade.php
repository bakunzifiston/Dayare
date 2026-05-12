@php $record = $record ?? null; @endphp
<section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        @include('farmer.health.partials.animal-select', ['animals' => $animals, 'selectedAnimalId' => $selectedAnimalId ?? $record?->animal_id, 'disabled' => $record !== null])
        <div><x-input-label for="visit_date" :value="__('Visit date')" /><x-text-input id="visit_date" name="visit_date" type="date" class="mt-1 block w-full" :value="old('visit_date', $record?->visit_date?->format('Y-m-d'))" max="{{ date('Y-m-d') }}" required /></div>
        <div><x-input-label for="veterinarian_name" :value="__('Veterinarian name')" /><x-text-input id="veterinarian_name" name="veterinarian_name" class="mt-1 block w-full" :value="old('veterinarian_name', $record?->veterinarian_name)" /></div>
        <div><x-input-label for="clinic_name" :value="__('Clinic name')" /><x-text-input id="clinic_name" name="clinic_name" class="mt-1 block w-full" :value="old('clinic_name', $record?->clinic_name)" /></div>
        <div class="sm:col-span-2"><x-input-label for="purpose_of_visit" :value="__('Purpose of visit')" /><x-text-input id="purpose_of_visit" name="purpose_of_visit" class="mt-1 block w-full" :value="old('purpose_of_visit', $record?->purpose_of_visit)" /></div>
        <div class="sm:col-span-2"><x-input-label for="findings" :value="__('Findings')" /><textarea id="findings" name="findings" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('findings', $record?->findings) }}</textarea></div>
        <div class="sm:col-span-2"><x-input-label for="recommendations" :value="__('Recommendations')" /><textarea id="recommendations" name="recommendations" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('recommendations', $record?->recommendations) }}</textarea></div>
        <div><x-input-label for="follow_up_date" :value="__('Follow-up date')" /><x-text-input id="follow_up_date" name="follow_up_date" type="date" class="mt-1 block w-full" :value="old('follow_up_date', $record?->follow_up_date?->format('Y-m-d'))" /></div>
        <div class="flex items-center gap-2 pt-7"><input id="follow_up_required" name="follow_up_required" type="checkbox" value="1" @checked(old('follow_up_required', $record?->follow_up_required)) class="rounded border-gray-300"><label for="follow_up_required" class="text-sm text-slate-700">{{ __('Follow-up required') }}</label></div>
        <div class="sm:col-span-2"><x-input-label for="notes" :value="__('Notes')" /><textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $record?->notes) }}</textarea></div>
        <div class="sm:col-span-2"><x-input-label for="attachment" :value="__('Attachment')" /><input id="attachment" name="attachment" type="file" accept=".pdf,image/*" class="mt-1 block w-full text-sm text-slate-600"></div>
    </div>
</section>
