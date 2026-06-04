@php
    $business = $business ?? null;
    $animalLabels = \App\Models\Business::animalsProcessedLabelMap();
    $productLabels = \App\Models\Business::productsSoldLabelMap();
    $segmentLabels = \App\Models\Business::customerSegmentsLabelMap();
@endphp
<div class="bucha-wizard-form space-y-8">
    <x-wizard-section :title="__('Business profile')" :subtitle="__('Size and revenue band help tailor onboarding and reporting.')">
        <x-wizard-field for="business_size" :label="__('Business size')" :hint="__('Based on typical employee count.')">
            <select id="business_size" name="business_size" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select business size') }}</option>
                @foreach (\App\Models\Business::BUSINESS_SIZES as $size)
                    <option value="{{ $size }}" @selected(old('business_size', $business?->business_size) === $size)>
                        {{ match($size) {
                            'micro' => __('Micro (1-2 employees)'),
                            'small' => __('Small (3-20 employees)'),
                            'medium' => __('Medium (21-100 employees)'),
                            'large' => __('Large (100+ employees)'),
                            default => ucfirst($size),
                        } }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('business_size')" />
        </x-wizard-field>

        <x-wizard-field for="baseline_revenue" :label="__('Baseline annual revenue band (RWF)')" :hint="__('Revenue bracket — optional if you enter an exact figure below.')">
            <select id="baseline_revenue" name="baseline_revenue" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select a range (optional)') }}</option>
                @foreach (\App\Models\Business::baselineRevenueBracketOptions() as $bracket => $bracketLabel)
                    <option value="{{ $bracket }}" @selected(old('baseline_revenue', $business?->baseline_revenue) === $bracket)>{{ $bracketLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('baseline_revenue')" />
        </x-wizard-field>

        <x-wizard-field for="baseline_revenue_rwf" :label="__('Average annual revenue before joining the programme (RWF)')" :hint="__('Exact figure — optional.')">
            <input id="baseline_revenue_rwf" name="baseline_revenue_rwf" type="number" min="0" step="1" class="bucha-wizard-input" value="{{ old('baseline_revenue_rwf', $business?->baseline_revenue_rwf) }}" data-wizard-track />
            <x-input-error class="mt-2" :messages="$errors->get('baseline_revenue_rwf')" />
        </x-wizard-field>
    </x-wizard-section>

    <x-wizard-section :title="__('Slaughterhouse operations')" :subtitle="__('Processing capacity, products, and customers.')">
        <x-wizard-field :label="__('Type of animals processed')">
            <div class="bucha-wizard-checkbox-grid">
                @foreach ($animalLabels as $value => $label)
                    <label class="bucha-wizard-checkbox">
                        <input type="checkbox" value="{{ $value }}" x-model="animalsProcessed" @change="syncSpeciesRows()" data-wizard-track />
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <template x-for="species in animalsProcessed" :key="'ap-'+species">
                <input type="hidden" name="animals_processed[]" :value="species" />
            </template>
            <div class="mt-3" x-show="animalsProcessed.includes('other')" x-cloak>
                <x-wizard-field for="animals_processed_other" :label="__('Other animals (please specify)')">
                    <input id="animals_processed_other" name="animals_processed_other" type="text" class="bucha-wizard-input" value="{{ old('animals_processed_other', $business?->animals_processed_other) }}" data-wizard-track />
                </x-wizard-field>
            </div>
        </x-wizard-field>

        <div x-show="dailyProcessing.length > 0" x-cloak class="space-y-3">
            <p class="text-sm font-semibold text-slate-700">{{ __('Average animals processed per day — per species') }}</p>
            <template x-for="(row, index) in dailyProcessing" :key="'dp-'+row.species">
                <div class="bucha-wizard-member-card">
                    <p class="text-sm font-medium text-slate-600" x-text="speciesLabel(row.species)"></p>
                    <input type="hidden" :name="'daily_processing['+index+'][species]'" :value="row.species" />
                    <div class="bucha-wizard-grid mt-2">
                        <x-wizard-field :label="__('Number of animals')">
                            <input type="number" min="0" step="1" class="bucha-wizard-input" x-model="row.number" :name="'daily_processing['+index+'][number]'" data-wizard-track />
                        </x-wizard-field>
                        <x-wizard-field :label="__('Quantity (kg)')">
                            <input type="number" min="0" step="0.01" class="bucha-wizard-input" x-model="row.quantity_kg" :name="'daily_processing['+index+'][quantity_kg]'" data-wizard-track />
                        </x-wizard-field>
                    </div>
                </div>
            </template>
        </div>

        <x-wizard-field :label="__('Main products sold')">
            @include('businesses.partials.wizard._survey-checkboxes', [
                'name' => 'products_sold',
                'options' => $productLabels,
                'selected' => old('products_sold', $business?->products_sold ?? []),
                'otherName' => 'products_sold_other',
                'otherValue' => old('products_sold_other', $business?->products_sold_other),
                'otherShowKey' => 'other',
            ])
            <x-input-error class="mt-2" :messages="$errors->get('products_sold')" />
        </x-wizard-field>

        <x-wizard-field :label="__('Main customer segments')">
            @include('businesses.partials.wizard._survey-checkboxes', [
                'name' => 'customer_segments',
                'options' => $segmentLabels,
                'selected' => old('customer_segments', $business?->customer_segments ?? []),
                'otherName' => 'customer_segments_other',
                'otherValue' => old('customer_segments_other', $business?->customer_segments_other),
                'otherShowKey' => 'other',
            ])
        </x-wizard-field>

        <div x-show="dailySalesKg.length > 0" x-cloak class="space-y-3">
            <p class="text-sm font-semibold text-slate-700">{{ __('Average daily meat sales (kg) — per species') }}</p>
            <template x-for="(row, index) in dailySalesKg" :key="'ds-'+row.species">
                <div class="bucha-wizard-member-card">
                    <p class="text-sm font-medium text-slate-600" x-text="speciesLabel(row.species)"></p>
                    <input type="hidden" :name="'daily_sales_kg['+index+'][species]'" :value="row.species" />
                    <x-wizard-field :label="__('Quantity (kg)')" class="mt-2">
                        <input type="number" min="0" step="0.01" class="bucha-wizard-input" x-model="row.quantity_kg" :name="'daily_sales_kg['+index+'][quantity_kg]'" data-wizard-track />
                    </x-wizard-field>
                </div>
            </template>
        </div>

        <x-wizard-field for="buyer_count" :label="__('Number of buyers supplied')">
            <input id="buyer_count" name="buyer_count" type="number" min="0" step="1" class="bucha-wizard-input" value="{{ old('buyer_count', $business?->buyer_count) }}" data-wizard-track />
            <x-input-error class="mt-2" :messages="$errors->get('buyer_count')" />
        </x-wizard-field>

        <x-wizard-field for="contract_type" :label="__('Contract buyers')">
            <select id="contract_type" name="contract_type" class="bucha-wizard-select" x-model="contractType" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="written_contracts" @selected(old('contract_type', $business?->contract_type) === 'written_contracts')>{{ __('Yes — written contracts') }}</option>
                <option value="verbal_agreements" @selected(old('contract_type', $business?->contract_type) === 'verbal_agreements')>{{ __('Yes — verbal agreements') }}</option>
                <option value="no_formal_contracts" @selected(old('contract_type', $business?->contract_type) === 'no_formal_contracts')>{{ __('No formal contracts') }}</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('contract_type')" />
        </x-wizard-field>

        <div x-show="contractType && contractType !== 'no_formal_contracts'" x-cloak>
            <x-wizard-field for="contracted_buyers" :label="__('List major contracted buyers')">
                <textarea id="contracted_buyers" name="contracted_buyers" rows="3" class="bucha-wizard-input" data-wizard-track>{{ old('contracted_buyers', $business?->contracted_buyers) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('contracted_buyers')" />
            </x-wizard-field>
        </div>

        <x-wizard-field for="digital_marketplace" :label="__('Integrated into a digital marketplace/platform')">
            <select id="digital_marketplace" name="digital_marketplace" class="bucha-wizard-select" x-model="digitalMarketplace" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="1">{{ __('Yes') }}</option>
                <option value="0">{{ __('No') }}</option>
            </select>
        </x-wizard-field>
        <div x-show="digitalMarketplace === '1'" x-cloak>
            <x-wizard-field for="digital_marketplace_name" :label="__('Marketplace / platform name')">
                <input id="digital_marketplace_name" name="digital_marketplace_name" type="text" class="bucha-wizard-input" value="{{ old('digital_marketplace_name', $business?->digital_marketplace_name) }}" data-wizard-track />
            </x-wizard-field>
        </div>
    </x-wizard-section>

    <x-wizard-section :title="__('Infrastructure & facilities')" :subtitle="__('Physical setup and utilities.')">
        @include('businesses.partials.wizard._survey-yes-no', ['name' => 'has_receiving_area', 'label' => __('Designated receiving area for live animals'), 'value' => $business?->has_receiving_area])
        <x-wizard-field for="road_condition" :label="__('Access road condition')">
            <select id="road_condition" name="road_condition" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                @foreach (\App\Models\Business::ROAD_CONDITIONS as $road)
                    <option value="{{ $road }}" @selected(old('road_condition', $business?->road_condition) === $road)>{{ __(ucfirst($road)) }}</option>
                @endforeach
            </select>
        </x-wizard-field>
        @include('businesses.partials.wizard._survey-yes-no', ['name' => 'has_potable_water', 'label' => __('Access to potable water on site'), 'value' => $business?->has_potable_water])
        <x-wizard-field for="waste_system" :label="__('Waste and effluent handling system')">
            <select id="waste_system" name="waste_system" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="functional" @selected(old('waste_system', $business?->waste_system) === 'functional')>{{ __('Yes – Functional') }}</option>
                <option value="needs_improvement" @selected(old('waste_system', $business?->waste_system) === 'needs_improvement')>{{ __('Yes – Needs Improvement') }}</option>
                <option value="none" @selected(old('waste_system', $business?->waste_system) === 'none')>{{ __('No') }}</option>
            </select>
        </x-wizard-field>
        <x-wizard-field for="has_cold_storage" :label="__('Cold storage / refrigeration')">
            <select id="has_cold_storage" name="has_cold_storage" class="bucha-wizard-select" x-model="hasColdStorage" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="1">{{ __('Yes') }}</option>
                <option value="0">{{ __('No') }}</option>
            </select>
        </x-wizard-field>
        <div x-show="hasColdStorage === '1'" x-cloak>
            <x-wizard-field for="cold_storage_capacity_kg" :label="__('Cold storage capacity (kg)')">
                <input id="cold_storage_capacity_kg" name="cold_storage_capacity_kg" type="number" min="0" class="bucha-wizard-input" value="{{ old('cold_storage_capacity_kg', $business?->cold_storage_capacity_kg) }}" data-wizard-track />
            </x-wizard-field>
        </div>
    </x-wizard-section>

    <x-wizard-section :title="__('Compliance & certifications')" :subtitle="__('Sanitary and operational compliance.')">
        <x-wizard-field for="sanitary_certificate" :label="__('Valid sanitary inspection certificate')">
            <select id="sanitary_certificate" name="sanitary_certificate" class="bucha-wizard-select" x-model="sanitaryCertificate" data-wizard-track>
                <option value="">{{ __('Select') }}</option>
                <option value="1">{{ __('Yes') }}</option>
                <option value="0">{{ __('No') }}</option>
            </select>
        </x-wizard-field>
        <div x-show="sanitaryCertificate === '1'" x-cloak>
            <x-wizard-field for="sanitary_certificate_expiry" :label="__('Certificate expiry date')">
                <input id="sanitary_certificate_expiry" name="sanitary_certificate_expiry" type="date" class="bucha-wizard-input" value="{{ old('sanitary_certificate_expiry', $business?->sanitary_certificate_expiry?->format('Y-m-d')) }}" data-wizard-track />
            </x-wizard-field>
        </div>
        @include('businesses.partials.wizard._survey-yes-no', ['name' => 'waste_disposal_plan', 'label' => __('Internal waste disposal plan'), 'value' => $business?->waste_disposal_plan])
        @include('businesses.partials.wizard._survey-yes-no', ['name' => 'has_sops', 'label' => __('Documented Standard Operating Procedures (SOPs)'), 'value' => $business?->has_sops])
        @include('businesses.partials.wizard._survey-yes-no', ['name' => 'workers_trained', 'label' => __('Workers trained in hygiene and safe handling'), 'value' => $business?->workers_trained])
    </x-wizard-section>
</div>
