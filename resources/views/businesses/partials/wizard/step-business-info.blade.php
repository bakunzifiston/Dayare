@php $business = $business ?? null; @endphp
<div class="bucha-wizard-form">
    <x-wizard-section :title="__('Business identity')" :subtitle="__('Official registration and contact details for this processor business.')">
        <x-wizard-field for="business_name" :label="__('Business name')" :hint="__('Legal or trading name as it appears on RDB records.')">
            <input id="business_name" name="business_name" type="text" class="bucha-wizard-input" value="{{ old('business_name', $business?->business_name) }}" data-wizard-track />
            <x-input-error class="mt-2" :messages="$errors->get('business_name')" />
        </x-wizard-field>

        <div class="bucha-wizard-grid">
            <x-wizard-field for="registration_number" :label="__('RDB registration number')" :hint="__('Unique company registration number.')">
                <input id="registration_number" name="registration_number" type="text" class="bucha-wizard-input" value="{{ old('registration_number', $business?->registration_number) }}" data-wizard-track />
                <x-input-error class="mt-2" :messages="$errors->get('registration_number')" />
            </x-wizard-field>
            <x-wizard-field for="tax_id" :label="__('Tax ID')" :hint="__('Optional TIN if already issued.')">
                <input id="tax_id" name="tax_id" type="text" class="bucha-wizard-input" value="{{ old('tax_id', $business?->tax_id) }}" data-wizard-track />
                <x-input-error class="mt-2" :messages="$errors->get('tax_id')" />
            </x-wizard-field>
        </div>
    </x-wizard-section>

    <x-wizard-section :title="__('Contact & status')" :subtitle="__('Primary phone, email, and account status.')">
        <div class="bucha-wizard-grid">
            <x-wizard-field for="contact_phone" :label="__('Contact phone')">
                <input id="contact_phone" name="contact_phone" type="tel" class="bucha-wizard-input" value="{{ old('contact_phone', $business?->contact_phone) }}" data-wizard-track />
                <x-input-error class="mt-2" :messages="$errors->get('contact_phone')" />
            </x-wizard-field>
            <x-wizard-field for="email" :label="__('Email')">
                <input id="email" name="email" type="email" class="bucha-wizard-input" value="{{ old('email', $business?->email) }}" data-wizard-track />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </x-wizard-field>
        </div>
        <x-wizard-field for="status" :label="__('Status')">
            <select id="status" name="status" class="bucha-wizard-select">
                @foreach (\App\Models\Business::STATUSES as $s)
                    <option value="{{ $s }}" @selected(old('status', $business?->status ?? 'active') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </x-wizard-field>
    </x-wizard-section>
</div>
