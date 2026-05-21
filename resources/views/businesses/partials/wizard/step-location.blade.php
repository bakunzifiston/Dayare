<div class="bucha-wizard-form">
    <x-wizard-section :title="__('Administrative location')" :subtitle="__('Select the full Rwanda administrative hierarchy from country down to village.')">
        <x-wizard-field for="country_id" :label="__('Country')">
            <select id="country_id" x-model="countryId" @change="onCountryChange()" class="bucha-wizard-select" data-wizard-track data-wizard-location>
                <option value="">{{ __('Select country') }}</option>
                <template x-for="d in countries" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('country_id')" />
        </x-wizard-field>

        <x-wizard-field for="province_id" :label="__('Province')">
            <select id="province_id" x-model="provinceId" @change="onProvinceChange()" class="bucha-wizard-select" data-wizard-track :disabled="!countryId" data-wizard-location>
                <option value="">{{ __('Select province') }}</option>
                <template x-for="d in provinces" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('province_id')" />
        </x-wizard-field>

        <x-wizard-field for="district_id" :label="__('District')">
            <select id="district_id" x-model="districtId" @change="onDistrictChange()" class="bucha-wizard-select" data-wizard-track :disabled="!provinceId" data-wizard-location>
                <option value="">{{ __('Select district') }}</option>
                <template x-for="d in districts" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('district_id')" />
        </x-wizard-field>

        <div class="bucha-wizard-grid">
            <x-wizard-field for="sector_id" :label="__('Sector')">
                <select id="sector_id" x-model="sectorId" @change="onSectorChange()" class="bucha-wizard-select" data-wizard-track :disabled="!districtId" data-wizard-location>
                    <option value="">{{ __('Select sector') }}</option>
                    <template x-for="d in sectors" :key="d.id">
                        <option :value="d.id" x-text="d.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('sector_id')" />
            </x-wizard-field>
            <x-wizard-field for="cell_id" :label="__('Cell')">
                <select id="cell_id" x-model="cellId" @change="onCellChange()" class="bucha-wizard-select" data-wizard-track :disabled="!sectorId" data-wizard-location>
                    <option value="">{{ __('Select cell') }}</option>
                    <template x-for="d in cells" :key="d.id">
                        <option :value="d.id" x-text="d.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('cell_id')" />
            </x-wizard-field>
        </div>

        <x-wizard-field for="village_id" :label="__('Village')" :hint="__('Optional — complete the hierarchy when address details are known.')">
            <select id="village_id" x-model="villageId" class="bucha-wizard-select" data-wizard-track :disabled="!cellId" data-wizard-location>
                <option value="">{{ __('Select village') }}</option>
                <template x-for="d in villages" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('village_id')" />
        </x-wizard-field>
    </x-wizard-section>
</div>
