@php $record = $record ?? null; @endphp
<section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        @include('farmer.health.partials.animal-select', ['animals' => $animals, 'selectedAnimalId' => $selectedAnimalId ?? $record?->animal_id, 'disabled' => $record !== null])
        <div><x-input-label for="death_date" :value="__('Death date')" /><x-text-input id="death_date" name="death_date" type="date" class="mt-1 block w-full" :value="old('death_date', $record?->death_date?->format('Y-m-d'))" max="{{ date('Y-m-d') }}" required /></div>
        <div class="sm:col-span-2"><x-input-label for="cause_of_death" :value="__('Cause of death')" /><x-text-input id="cause_of_death" name="cause_of_death" class="mt-1 block w-full" :value="old('cause_of_death', $record?->cause_of_death)" required /></div>
        <div><x-input-label for="reported_by" :value="__('Reported by')" /><x-text-input id="reported_by" name="reported_by" class="mt-1 block w-full" :value="old('reported_by', $record?->reported_by)" /></div>
        <div><x-input-label for="veterinarian_name" :value="__('Veterinarian name')" /><x-text-input id="veterinarian_name" name="veterinarian_name" class="mt-1 block w-full" :value="old('veterinarian_name', $record?->veterinarian_name)" /></div>
        <div><x-input-label for="disposal_method" :value="__('Disposal method')" /><x-text-input id="disposal_method" name="disposal_method" class="mt-1 block w-full" :value="old('disposal_method', $record?->disposal_method)" /></div>
        <div class="flex items-center gap-2 pt-7"><input id="postmortem_done" name="postmortem_done" type="checkbox" value="1" @checked(old('postmortem_done', $record?->postmortem_done)) class="rounded border-gray-300"><label for="postmortem_done" class="text-sm text-slate-700">{{ __('Postmortem done') }}</label></div>
        <div class="sm:col-span-2"><x-input-label for="notes" :value="__('Notes')" /><textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $record?->notes) }}</textarea></div>
        <div class="sm:col-span-2"><x-input-label for="attachment" :value="__('Attachment')" /><input id="attachment" name="attachment" type="file" accept=".pdf,image/*" class="mt-1 block w-full text-sm text-slate-600"></div>
    </div>
</section>
