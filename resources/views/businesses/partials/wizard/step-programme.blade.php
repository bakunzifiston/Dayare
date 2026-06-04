@php $business = $business ?? null; @endphp
<div class="bucha-wizard-form">
    <x-wizard-section :title="__('VIBE metadata')" :subtitle="__('Tracking fields used by the VIBE pathway.')">
        <x-wizard-field for="vibe_unique_id" :label="__('VIBE unique ID')" :hint="__('Assigned automatically after the business is saved.')">
            <input id="vibe_unique_id" name="vibe_unique_id" type="text" class="bucha-wizard-input bg-slate-50 text-slate-600" value="{{ old('vibe_unique_id', $business?->vibe_unique_id) }}" readonly />
            <x-input-error class="mt-2" :messages="$errors->get('vibe_unique_id')" />
        </x-wizard-field>

        <div class="bucha-wizard-grid">
            <x-wizard-field for="vibe_commencement_date" :label="__('VIBE commencement date')">
                <input id="vibe_commencement_date" name="vibe_commencement_date" type="date" class="bucha-wizard-input" data-wizard-track value="{{ old('vibe_commencement_date', $business?->vibe_commencement_date?->format('Y-m-d')) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('vibe_commencement_date')" />
            </x-wizard-field>
            <x-wizard-field for="pathway_status" :label="__('Pathway status')">
                <select id="pathway_status" name="pathway_status" class="bucha-wizard-select">
                    @foreach (\App\Models\Business::PATHWAY_STATUSES as $pathwayStatus)
                        <option value="{{ $pathwayStatus }}" @selected(old('pathway_status', $business?->pathway_status ?? 'active') === $pathwayStatus)>{{ __(ucfirst($pathwayStatus)) }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('pathway_status')" />
            </x-wizard-field>
        </div>

        <x-wizard-field for="vibe_comments" :label="__('VIBE comments')">
            <textarea id="vibe_comments" name="vibe_comments" rows="4" class="bucha-wizard-input" data-wizard-track">{{ old('vibe_comments', $business?->vibe_comments) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('vibe_comments')" />
        </x-wizard-field>
    </x-wizard-section>
</div>
