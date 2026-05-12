@php
    $certificate = $certificate ?? null;
    $selectedAnimalId = $selectedAnimalId ?? null;
@endphp
<section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        @isset($animals)
            <div><x-input-label for="animal_id" :value="__('Animal')" /><select id="animal_id" name="animal_id" class="mt-1 block w-full rounded-lg border-gray-300" required><option value="">{{ __('Select animal') }}</option>@foreach ($animals as $animal)<option value="{{ $animal->id }}" @selected((int) old('animal_id', $certificate?->animal_id ?: $selectedAnimalId) === $animal->id)>{{ $animal->selectionLabel() }}</option>@endforeach</select></div>
        @endisset
        <div><x-input-label for="certificate_type" :value="__('Certificate type')" /><select id="certificate_type" name="certificate_type" class="mt-1 block w-full rounded-lg border-gray-300" required>@foreach (\App\Models\AnimalCertificate::TYPES as $type)<option value="{{ $type }}" @selected(old('certificate_type', $certificate?->certificate_type) === $type)>{{ __(ucwords(str_replace('_', ' ', $type))) }}</option>@endforeach</select></div>
        <div><x-input-label for="template_id" :value="__('Template')" /><select id="template_id" name="template_id" class="mt-1 block w-full rounded-lg border-gray-300"><option value="">{{ __('Default template') }}</option>@foreach ($templates ?? [] as $template)<option value="{{ $template->id }}" @selected((int) old('template_id', $certificate?->template_id) === $template->id)>{{ $template->template_name }}</option>@endforeach</select></div>
        <div><x-input-label for="certificate_title" :value="__('Certificate title')" /><x-text-input id="certificate_title" name="certificate_title" class="mt-1 block w-full" :value="old('certificate_title', $certificate?->certificate_title)" /></div>
        <div><x-input-label for="issue_date" :value="__('Issue date')" /><x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', $certificate?->issue_date?->format('Y-m-d') ?: date('Y-m-d'))" max="{{ date('Y-m-d') }}" required /></div>
        <div><x-input-label for="expiry_date" :value="__('Expiry date')" /><x-text-input id="expiry_date" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date', $certificate?->expiry_date?->format('Y-m-d'))" /></div>
        <div><x-input-label for="issued_by" :value="__('Issued by')" /><x-text-input id="issued_by" name="issued_by" class="mt-1 block w-full" :value="old('issued_by', $certificate?->issued_by)" /></div>
        <div><x-input-label for="veterinarian_name" :value="__('Veterinarian name')" /><x-text-input id="veterinarian_name" name="veterinarian_name" class="mt-1 block w-full" :value="old('veterinarian_name', $certificate?->veterinarian_name)" /></div>
        <div class="sm:col-span-2"><x-input-label for="remarks" :value="__('Remarks')" /><textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('remarks', $certificate?->remarks) }}</textarea></div>
    </div>
</section>
