@php $business = $business ?? null; @endphp
<div class="bucha-wizard-form">
    <x-wizard-section :title="__('Workforce headcount')" :subtitle="__('Required for VIBE reporting. All fields are optional.')">
        <x-wizard-field for="total_employees" :label="__('Total full-time employees')" :hint="__('If owner only, enter 1.')">
            <input id="total_employees" name="total_employees" type="number" min="0" class="bucha-wizard-input" value="{{ old('total_employees', $business?->total_employees) }}" data-wizard-track @input="calcWorkforceSplits()" />
            <x-input-error class="mt-2" :messages="$errors->get('total_employees')" />
        </x-wizard-field>

        <div class="bucha-wizard-grid">
            <x-wizard-field for="female_employees" :label="__('Female employees')">
                <input id="female_employees" name="female_employees" type="number" min="0" class="bucha-wizard-input" value="{{ old('female_employees', $business?->female_employees) }}" data-wizard-track @input="calcWorkforceSplits()" />
            </x-wizard-field>
            <x-wizard-field :label="__('Male employees (calculated)')">
                <input type="text" class="bucha-wizard-input bg-slate-50" readonly x-model="maleEmployeesCalc" tabindex="-1" />
            </x-wizard-field>
        </div>

        <div class="bucha-wizard-grid">
            <x-wizard-field for="employees_18_35" :label="__('Employees aged 18–35')">
                <input id="employees_18_35" name="employees_18_35" type="number" min="0" class="bucha-wizard-input" value="{{ old('employees_18_35', $business?->employees_18_35) }}" data-wizard-track @input="calcWorkforceSplits()" />
            </x-wizard-field>
            <x-wizard-field for="female_employees_18_35" :label="__('Female employees aged 18–35')">
                <input id="female_employees_18_35" name="female_employees_18_35" type="number" min="0" class="bucha-wizard-input" value="{{ old('female_employees_18_35', $business?->female_employees_18_35) }}" data-wizard-track @input="calcWorkforceSplits()" />
            </x-wizard-field>
        </div>

        <div class="bucha-wizard-grid">
            <x-wizard-field :label="__('Male employees 18–35 (calculated)')">
                <input type="text" class="bucha-wizard-input bg-slate-50" readonly x-model="maleEmployees1835Calc" tabindex="-1" />
            </x-wizard-field>
            <x-wizard-field :label="__('Employees 36+ (calculated)')">
                <input type="text" class="bucha-wizard-input bg-slate-50" readonly x-model="employees36PlusCalc" tabindex="-1" />
            </x-wizard-field>
        </div>

        <div class="bucha-wizard-grid">
            <x-wizard-field for="pwd_employees" :label="__('Employees with a disability')" :hint="__('Enter 0 if none.')">
                <input id="pwd_employees" name="pwd_employees" type="number" min="0" class="bucha-wizard-input" value="{{ old('pwd_employees', $business?->pwd_employees) }}" data-wizard-track />
            </x-wizard-field>
            <x-wizard-field for="refugee_employees" :label="__('Refugee employees')">
                <input id="refugee_employees" name="refugee_employees" type="number" min="0" class="bucha-wizard-input" value="{{ old('refugee_employees', $business?->refugee_employees) }}" data-wizard-track />
            </x-wizard-field>
        </div>

        <x-wizard-field for="seasonal_workers" :label="__('Seasonal workers')">
            <input id="seasonal_workers" name="seasonal_workers" type="number" min="0" class="bucha-wizard-input" value="{{ old('seasonal_workers', $business?->seasonal_workers) }}" data-wizard-track />
        </x-wizard-field>

        <x-wizard-field for="has_dedicated_manager" :label="__('Dedicated manager (not the owner)')">
            <select id="has_dedicated_manager" name="has_dedicated_manager" class="bucha-wizard-select" x-model="hasDedicatedManager" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="full_time" @selected(old('has_dedicated_manager', $business?->has_dedicated_manager) === 'full_time')>{{ __('Yes — full-time') }}</option>
                <option value="self_managed" @selected(old('has_dedicated_manager', $business?->has_dedicated_manager) === 'self_managed')>{{ __('I manage it myself') }}</option>
                <option value="no_manager" @selected(old('has_dedicated_manager', $business?->has_dedicated_manager) === 'no_manager')>{{ __('No dedicated manager') }}</option>
            </select>
        </x-wizard-field>

        <div x-show="hasDedicatedManager === 'full_time'" x-cloak class="bucha-wizard-grid">
            <x-wizard-field for="manager_first_name" :label="__('Manager first name')">
                <input id="manager_first_name" name="manager_first_name" type="text" class="bucha-wizard-input" value="{{ old('manager_first_name', $business?->manager_first_name) }}" data-wizard-track />
            </x-wizard-field>
            <x-wizard-field for="manager_gender" :label="__('Manager sex')">
                <select id="manager_gender" name="manager_gender" class="bucha-wizard-select" data-wizard-track>
                    <option value="">{{ __('Select gender') }}</option>
                    @foreach (['male', 'female'] as $g)
                        <option value="{{ $g }}" @selected(old('manager_gender', $business?->manager_gender) === $g)>{{ __(ucfirst($g)) }}</option>
                    @endforeach
                </select>
            </x-wizard-field>
            <x-wizard-field for="manager_age" :label="__('Manager age (years)')">
                <input id="manager_age" name="manager_age" type="number" min="0" max="120" class="bucha-wizard-input" value="{{ old('manager_age', $business?->manager_age) }}" data-wizard-track />
            </x-wizard-field>
        </div>
    </x-wizard-section>
</div>
