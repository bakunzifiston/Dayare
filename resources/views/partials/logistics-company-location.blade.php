@php
    $oProv = old('province_id', $provinceId ?? '');
    $oDist = old('district_id', $districtId ?? '');
    $oSec = old('sector_id', $sectorId ?? '');
    $oCell = old('cell_id', $cellId ?? '');
    $oVill = old('village_id', $villageId ?? '');
    $oCountry = old('country_id', $countryId ?? '');
@endphp
<div class="md:col-span-3 rounded-lg border border-slate-200 bg-slate-50/80 p-4" x-data="logisticsCompanyLocationDropdowns()" x-init="loadCountries()">
    <input type="hidden" name="country_id" :value="countryId || ''">
    <input type="hidden" name="province_id" :value="provinceId || ''">
    <input type="hidden" name="district_id" :value="districtId || ''">
    <input type="hidden" name="sector_id" :value="sectorId || ''">
    <input type="hidden" name="cell_id" :value="cellId || ''">
    <input type="hidden" name="village_id" :value="villageId || ''">
    <h3 class="text-sm font-semibold text-slate-800">{{ __('Company location') }}</h3>
    <p class="mt-0.5 text-xs text-slate-500">{{ __('Select country through village (each level depends on the previous).') }}</p>
    <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
        <div>
            <label for="logistics_loc_country" class="block text-xs font-medium text-slate-700">{{ __('Country') }}</label>
            <select id="logistics_loc_country" x-model="countryId" @change="onCountryChange()" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                <option value="">{{ __('Select country') }}</option>
                <template x-for="d in countries" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="logistics_loc_province" class="block text-xs font-medium text-slate-700">{{ __('Province') }}</label>
            <select id="logistics_loc_province" x-model="provinceId" @change="onProvinceChange()" class="mt-1 block w-full rounded-md border-slate-300 text-sm" :disabled="!countryId">
                <option value="">{{ __('Select province') }}</option>
                <template x-for="d in provinces" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="logistics_loc_district" class="block text-xs font-medium text-slate-700">{{ __('District') }}</label>
            <select id="logistics_loc_district" x-model="districtId" @change="onDistrictChange()" class="mt-1 block w-full rounded-md border-slate-300 text-sm" :disabled="!provinceId">
                <option value="">{{ __('Select district') }}</option>
                <template x-for="d in districts" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="logistics_loc_sector" class="block text-xs font-medium text-slate-700">{{ __('Sector') }}</label>
            <select id="logistics_loc_sector" x-model="sectorId" @change="onSectorChange()" class="mt-1 block w-full rounded-md border-slate-300 text-sm" :disabled="!districtId">
                <option value="">{{ __('Select sector') }}</option>
                <template x-for="d in sectors" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="logistics_loc_cell" class="block text-xs font-medium text-slate-700">{{ __('Cell') }}</label>
            <select id="logistics_loc_cell" x-model="cellId" @change="onCellChange()" class="mt-1 block w-full rounded-md border-slate-300 text-sm" :disabled="!sectorId">
                <option value="">{{ __('Select cell') }}</option>
                <template x-for="d in cells" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="logistics_loc_village" class="block text-xs font-medium text-slate-700">{{ __('Village') }}</label>
            <select id="logistics_loc_village" x-model="villageId" class="mt-1 block w-full rounded-md border-slate-300 text-sm" :disabled="!cellId">
                <option value="">{{ __('Select village') }}</option>
                <template x-for="d in villages" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
        </div>
    </div>
</div>
<script>
    function logisticsCompanyLocationDropdowns() {
        const baseUrl = '{{ route("divisions.index") }}';
        return {
            countries: [], provinces: [], districts: [], sectors: [], cells: [], villages: [],
            countryId: @json($oCountry),
            provinceId: @json($oProv),
            districtId: @json($oDist),
            sectorId: @json($oSec),
            cellId: @json($oCell),
            villageId: @json($oVill),
            async fetchChildren(parentId) {
                try {
                    const url = parentId ? `${baseUrl}?parent_id=${parentId}` : baseUrl;
                    const res = await fetch(url, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await res.json();
                    return Array.isArray(data) ? data : [];
                } catch (e) { return []; }
            },
            async loadCountries() {
                try {
                    this.countries = await this.fetchChildren(null);
                    await this.restoreCascade();
                } catch (e) { this.countries = []; }
            },
            async restoreCascade() {
                if (this.countryId) {
                    this.provinces = await this.fetchChildren(this.countryId);
                    if (this.provinceId) {
                        this.districts = await this.fetchChildren(this.provinceId);
                        if (this.districtId) {
                            this.sectors = await this.fetchChildren(this.districtId);
                            if (this.sectorId) {
                                this.cells = await this.fetchChildren(this.sectorId);
                                if (this.cellId) this.villages = await this.fetchChildren(this.cellId);
                            }
                        }
                    }
                }
            },
            async onCountryChange() {
                this.provinceId = this.districtId = this.sectorId = this.cellId = this.villageId = '';
                this.provinces = this.districts = this.sectors = this.cells = this.villages = [];
                if (this.countryId) this.provinces = await this.fetchChildren(this.countryId);
            },
            async onProvinceChange() {
                this.districtId = this.sectorId = this.cellId = this.villageId = '';
                this.districts = this.sectors = this.cells = this.villages = [];
                if (this.provinceId) this.districts = await this.fetchChildren(this.provinceId);
            },
            async onDistrictChange() {
                this.sectorId = this.cellId = this.villageId = '';
                this.sectors = this.cells = this.villages = [];
                if (this.districtId) this.sectors = await this.fetchChildren(this.districtId);
            },
            async onSectorChange() {
                this.cellId = this.villageId = '';
                this.cells = this.villages = [];
                if (this.sectorId) this.cells = await this.fetchChildren(this.sectorId);
            },
            async onCellChange() {
                this.villageId = '';
                this.villages = [];
                if (this.cellId) this.villages = await this.fetchChildren(this.cellId);
            }
        };
    }
</script>
