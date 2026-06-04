@php
    $business = $business ?? null;
    $deviceLabels = \App\Models\Business::digitalDevicesLabelMap();
@endphp
<div class="bucha-wizard-form space-y-8">
    <x-wizard-section :title="__('Banking & payments')" :subtitle="__('Financial readiness for the programme.')">
        <x-wizard-field for="bank_account" :label="__('Bank account')">
            <select id="bank_account" name="bank_account" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="business_account" @selected(old('bank_account', $business?->bank_account) === 'business_account')>{{ __('Yes — business account') }}</option>
                <option value="personal_account" @selected(old('bank_account', $business?->bank_account) === 'personal_account')>{{ __('Yes — personal account only') }}</option>
                <option value="no_account" @selected(old('bank_account', $business?->bank_account) === 'no_account')>{{ __('No') }}</option>
            </select>
        </x-wizard-field>
        <x-wizard-field for="uses_mobile_money" :label="__('Uses mobile money for transactions')">
            <select id="uses_mobile_money" name="uses_mobile_money" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                @foreach (\App\Models\Business::MOBILE_MONEY_USAGE as $opt)
                    <option value="{{ $opt }}" @selected(old('uses_mobile_money', $business?->uses_mobile_money) === $opt)>{{ __(ucfirst($opt)) }}</option>
                @endforeach
            </select>
        </x-wizard-field>
        <x-wizard-field for="digital_payment_willingness" :label="__('Willing to receive digital payments')">
            <select id="digital_payment_willingness" name="digital_payment_willingness" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="prefer_digital" @selected(old('digital_payment_willingness', $business?->digital_payment_willingness) === 'prefer_digital')>{{ __('Yes — prefer digital') }}</option>
                <option value="willing_try" @selected(old('digital_payment_willingness', $business?->digital_payment_willingness) === 'willing_try')>{{ __('Yes — willing to try') }}</option>
                <option value="unsure" @selected(old('digital_payment_willingness', $business?->digital_payment_willingness) === 'unsure')>{{ __('Unsure') }}</option>
                <option value="prefer_cash" @selected(old('digital_payment_willingness', $business?->digital_payment_willingness) === 'prefer_cash')>{{ __('No — prefer cash/bank') }}</option>
            </select>
        </x-wizard-field>
    </x-wizard-section>

    <x-wizard-section :title="__('Digital systems & devices')" :subtitle="__('Technology and connectivity.')">
        <x-wizard-field for="uses_digital_records" :label="__('Uses digital record-keeping system')">
            <select id="uses_digital_records" name="uses_digital_records" class="bucha-wizard-select" x-model="usesDigitalRecords" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="1">{{ __('Yes') }}</option>
                <option value="0">{{ __('No') }}</option>
            </select>
        </x-wizard-field>
        <div x-show="usesDigitalRecords === '1'" x-cloak>
            <x-wizard-field for="digital_system_name" :label="__('Which system?')">
                <input id="digital_system_name" name="digital_system_name" type="text" class="bucha-wizard-input" value="{{ old('digital_system_name', $business?->digital_system_name) }}" data-wizard-track />
            </x-wizard-field>
        </div>
        <x-wizard-field :label="__('Digital devices available')">
            @include('businesses.partials.wizard._survey-checkboxes', [
                'name' => 'digital_devices',
                'options' => $deviceLabels,
                'selected' => old('digital_devices', $business?->digital_devices ?? []),
            ])
        </x-wizard-field>
        <x-wizard-field for="network_connectivity" :label="__('Network connectivity')">
            <select id="network_connectivity" name="network_connectivity" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="strong_4g" @selected(old('network_connectivity', $business?->network_connectivity) === 'strong_4g')>{{ __('Strong 4G/LTE') }}</option>
                <option value="moderate_3g" @selected(old('network_connectivity', $business?->network_connectivity) === 'moderate_3g')>{{ __('Moderate 3G') }}</option>
                <option value="weak_intermittent" @selected(old('network_connectivity', $business?->network_connectivity) === 'weak_intermittent')>{{ __('Weak — intermittent') }}</option>
                <option value="no_signal" @selected(old('network_connectivity', $business?->network_connectivity) === 'no_signal')>{{ __('No signal (offline only)') }}</option>
            </select>
        </x-wizard-field>
        <x-wizard-field for="digital_ledger_willingness" :label="__('Willing to transition to a daily digital ledger')">
            <select id="digital_ledger_willingness" name="digital_ledger_willingness" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="fully_willing" @selected(old('digital_ledger_willingness', $business?->digital_ledger_willingness) === 'fully_willing')>{{ __('Yes — fully willing') }}</option>
                <option value="needs_training" @selected(old('digital_ledger_willingness', $business?->digital_ledger_willingness) === 'needs_training')>{{ __('Yes — will need training') }}</option>
                <option value="unsure" @selected(old('digital_ledger_willingness', $business?->digital_ledger_willingness) === 'unsure')>{{ __('Unsure') }}</option>
                <option value="prefer_current" @selected(old('digital_ledger_willingness', $business?->digital_ledger_willingness) === 'prefer_current')>{{ __('No — prefer current system') }}</option>
            </select>
        </x-wizard-field>
    </x-wizard-section>
</div>
