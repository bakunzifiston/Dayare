{{--
  Cascading Rwanda admin location for certificate PDF fields.
  Props:
    $prefix     string  e.g. selling | facility | destination
    $targetKey  string  composed string key e.g. selling_location
    $required   bool
    $includeVillage bool
    $showPlaceName bool  (destination name free text)
    $pdfValue   callable
--}}
@php
    $prefix = $prefix ?? 'location';
    $targetKey = $targetKey ?? 'location';
    $required = (bool) ($required ?? false);
    $includeVillage = (bool) ($includeVillage ?? false);
    $showPlaceName = (bool) ($showPlaceName ?? false);
    $label = $label ?? __('Location');
    $componentId = 'cert-loc-'.$prefix;
    $pdfValue = $pdfValue ?? fn (string $key) => '';

    $oCountry = '';
    $oProvince = (string) $pdfValue("{$prefix}_province_id");
    $oDistrict = (string) $pdfValue("{$prefix}_district_id");
    $oSector = (string) $pdfValue("{$prefix}_sector_id");
    $oCell = (string) $pdfValue("{$prefix}_cell_id");
    $oVillage = (string) $pdfValue("{$prefix}_village_id");
    $oPlace = (string) $pdfValue('destination_place_name');
    $oComposed = (string) $pdfValue($targetKey);
@endphp

<div
    id="{{ $componentId }}"
    class="space-y-3 rounded-lg border border-slate-200 bg-slate-50/50 p-4"
    x-data="certificateLocationCascade({
        prefix: @js($prefix),
        targetKey: @js($targetKey),
        includeVillage: @js($includeVillage),
        showPlaceName: @js($showPlaceName),
        provinceId: @js($oProvince),
        districtId: @js($oDistrict),
        sectorId: @js($oSector),
        cellId: @js($oCell),
        villageId: @js($oVillage),
        placeName: @js($oPlace),
        initialComposed: @js($oComposed),
        divisionsUrl: @js(route('divisions.index')),
    })"
    x-init="init()"
>
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>

    @if ($showPlaceName)
        <div>
            <label for="{{ $componentId }}_place" class="text-xs font-medium text-slate-600">{{ __('Destination name (optional)') }}</label>
            <input
                id="{{ $componentId }}_place"
                type="text"
                class="bucha-wizard-input mt-1"
                x-model="placeName"
                @input="syncComposed()"
                placeholder="{{ __('e.g. Kigali Butchery') }}"
            >
            <input type="hidden" name="pdf_details[destination_place_name]" :value="placeName">
        </div>
    @endif

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label for="{{ $componentId }}_country" class="text-xs font-medium text-slate-600">{{ __('Country') }}</label>
            <select id="{{ $componentId }}_country" x-model="countryId" @change="onCountryChange()" class="bucha-wizard-select mt-1">
                <option value="">{{ __('Select country') }}</option>
                <template x-for="d in countries" :key="d.id">
                    <option :value="String(d.id)" x-text="d.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="{{ $componentId }}_province" class="text-xs font-medium text-slate-600">{{ __('Province') }} @if ($required)<span class="text-red-600">*</span>@endif</label>
            <select id="{{ $componentId }}_province" name="pdf_details[{{ $prefix }}_province_id]" x-model="provinceId" @change="onProvinceChange()" class="bucha-wizard-select mt-1" :disabled="!countryId">
                <option value="">{{ __('Select province') }}</option>
                <template x-for="d in provinces" :key="d.id">
                    <option :value="String(d.id)" x-text="d.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="{{ $componentId }}_district" class="text-xs font-medium text-slate-600">{{ __('District') }} @if ($required)<span class="text-red-600">*</span>@endif</label>
            <select id="{{ $componentId }}_district" name="pdf_details[{{ $prefix }}_district_id]" x-model="districtId" @change="onDistrictChange()" class="bucha-wizard-select mt-1" :disabled="!provinceId">
                <option value="">{{ __('Select district') }}</option>
                <template x-for="d in districts" :key="d.id">
                    <option :value="String(d.id)" x-text="d.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="{{ $componentId }}_sector" class="text-xs font-medium text-slate-600">{{ __('Sector') }} @if ($required)<span class="text-red-600">*</span>@endif</label>
            <select id="{{ $componentId }}_sector" name="pdf_details[{{ $prefix }}_sector_id]" x-model="sectorId" @change="onSectorChange()" class="bucha-wizard-select mt-1" :disabled="!districtId">
                <option value="">{{ __('Select sector') }}</option>
                <template x-for="d in sectors" :key="d.id">
                    <option :value="String(d.id)" x-text="d.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="{{ $componentId }}_cell" class="text-xs font-medium text-slate-600">{{ __('Cell') }} @if ($required)<span class="text-red-600">*</span>@endif</label>
            <select id="{{ $componentId }}_cell" name="pdf_details[{{ $prefix }}_cell_id]" x-model="cellId" @change="onCellChange()" class="bucha-wizard-select mt-1" :disabled="!sectorId">
                <option value="">{{ __('Select cell') }}</option>
                <template x-for="d in cells" :key="d.id">
                    <option :value="String(d.id)" x-text="d.name"></option>
                </template>
            </select>
        </div>
        @if ($includeVillage)
            <div>
                <label for="{{ $componentId }}_village" class="text-xs font-medium text-slate-600">{{ __('Village') }}</label>
                <select id="{{ $componentId }}_village" name="pdf_details[{{ $prefix }}_village_id]" x-model="villageId" @change="syncComposed()" class="bucha-wizard-select mt-1" :disabled="!cellId">
                    <option value="">{{ __('Select village') }}</option>
                    <template x-for="d in villages" :key="d.id">
                        <option :value="String(d.id)" x-text="d.name"></option>
                    </template>
                </select>
            </div>
        @endif
    </div>

    <input type="hidden" :name="'pdf_details[' + targetKey + ']'" :value="composedLine">
    <p class="text-xs text-slate-500" x-show="composedLine" x-text="composedLine"></p>
</div>

@once
<script>
    function certificateLocationCascade(config) {
        const baseUrl = config.divisionsUrl;
        return {
            countries: [], provinces: [], districts: [], sectors: [], cells: [], villages: [],
            countryId: '',
            provinceId: String(config.provinceId || ''),
            districtId: String(config.districtId || ''),
            sectorId: String(config.sectorId || ''),
            cellId: String(config.cellId || ''),
            villageId: String(config.villageId || ''),
            placeName: String(config.placeName || ''),
            includeVillage: !!config.includeVillage,
            showPlaceName: !!config.showPlaceName,
            targetKey: config.targetKey,
            composedLine: String(config.initialComposed || ''),
            async fetchChildren(parentId) {
                try {
                    const url = parentId ? `${baseUrl}?parent_id=${parentId}` : baseUrl;
                    const res = await fetch(url, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await res.json();
                    return Array.isArray(data) ? data : [];
                } catch (e) {
                    return [];
                }
            },
            nameById(list, id) {
                if (!id) return '';
                const row = list.find(d => String(d.id) === String(id));
                return row ? row.name : '';
            },
            syncComposed() {
                const parts = [
                    this.nameById(this.districts, this.districtId),
                    this.nameById(this.sectors, this.sectorId),
                    this.nameById(this.cells, this.cellId),
                ];
                if (this.includeVillage) {
                    parts.push(this.nameById(this.villages, this.villageId));
                }
                const location = parts.filter(Boolean).join(', ');
                if (this.showPlaceName && this.placeName && location) {
                    this.composedLine = `${this.placeName} — ${location}`;
                } else if (this.showPlaceName && this.placeName) {
                    this.composedLine = this.placeName;
                } else if (location) {
                    this.composedLine = location;
                }
                // Keep initial composed text when cascade not yet chosen (legacy certificates).
            },
            async init() {
                this.countries = await this.fetchChildren(null);
                const rwanda = this.countries.find(c => (c.code || '').toUpperCase() === 'RW' || c.name === 'Rwanda');
                if (rwanda) {
                    this.countryId = String(rwanda.id);
                }
                await this.restoreCascade();
                this.syncComposed();
            },
            async restoreCascade() {
                if (!this.countryId) return;
                this.provinces = await this.fetchChildren(this.countryId);
                if (this.provinceId) {
                    this.districts = await this.fetchChildren(this.provinceId);
                    if (this.districtId) {
                        this.sectors = await this.fetchChildren(this.districtId);
                        if (this.sectorId) {
                            this.cells = await this.fetchChildren(this.sectorId);
                            if (this.includeVillage && this.cellId) {
                                this.villages = await this.fetchChildren(this.cellId);
                            }
                        }
                    }
                }
            },
            async onCountryChange() {
                this.provinceId = this.districtId = this.sectorId = this.cellId = this.villageId = '';
                this.provinces = this.districts = this.sectors = this.cells = this.villages = [];
                if (this.countryId) this.provinces = await this.fetchChildren(this.countryId);
                this.syncComposed();
            },
            async onProvinceChange() {
                this.districtId = this.sectorId = this.cellId = this.villageId = '';
                this.districts = this.sectors = this.cells = this.villages = [];
                if (this.provinceId) this.districts = await this.fetchChildren(this.provinceId);
                this.syncComposed();
            },
            async onDistrictChange() {
                this.sectorId = this.cellId = this.villageId = '';
                this.sectors = this.cells = this.villages = [];
                if (this.districtId) this.sectors = await this.fetchChildren(this.districtId);
                this.syncComposed();
            },
            async onSectorChange() {
                this.cellId = this.villageId = '';
                this.cells = this.villages = [];
                if (this.sectorId) this.cells = await this.fetchChildren(this.sectorId);
                this.syncComposed();
            },
            async onCellChange() {
                this.villageId = '';
                this.villages = [];
                if (this.includeVillage && this.cellId) this.villages = await this.fetchChildren(this.cellId);
                this.syncComposed();
            },
        };
    }
</script>
@endonce
