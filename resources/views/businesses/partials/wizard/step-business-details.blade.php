<div class="bucha-wizard-form">
    <x-wizard-section :title="__('Business profile')" :subtitle="__('Size and revenue band help tailor onboarding and reporting.')">
        <x-wizard-field for="business_size" :label="__('Business size')" :hint="__('Based on typical employee count.')">
            <select id="business_size" name="business_size" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select business size') }}</option>
                <option value="micro" @selected(old('business_size') === 'micro')>{{ __('Micro (1-2 employees)') }}</option>
                <option value="small" @selected(old('business_size') === 'small')>{{ __('Small (3-20 employees)') }}</option>
                <option value="medium" @selected(old('business_size') === 'medium')>{{ __('Medium (21-100 employees)') }}</option>
                <option value="large" @selected(old('business_size') === 'large')>{{ __('Large (100+ employees)') }}</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('business_size')" />
        </x-wizard-field>

        <x-wizard-field for="baseline_revenue" :label="__('Baseline annual revenue (RWF)')" :hint="__('Choose the closest annual revenue band.')">
            <select id="baseline_revenue" name="baseline_revenue" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select a range (optional)') }}</option>
                @foreach (\App\Models\Business::baselineRevenueBracketOptions() as $bracket => $bracketLabel)
                    <option value="{{ $bracket }}" @selected(old('baseline_revenue') === $bracket)>{{ $bracketLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('baseline_revenue')" />
        </x-wizard-field>
    </x-wizard-section>
</div>
