<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Facility') }} — {{ $business->business_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('businesses.facilities.store', $business) }}" class="space-y-6" id="facility-create-form" onsubmit="var f=document.getElementById('facility-create-form');if(f){['country_id','province_id','district_id','sector_id','cell_id','village_id'].forEach(function(id){var s=document.getElementById(id),h=f.querySelector('input[name='+id+']');if(s&&h)h.value=s?s.value:'';});}return true;">
                    @csrf

                    <div>
                        <x-input-label for="facility_name" :value="__('Facility Name')" />
                        <x-text-input id="facility_name" name="facility_name" type="text" class="mt-1 block w-full" :value="old('facility_name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('facility_name')" />
                    </div>

                    <div>
                        <x-input-label for="facility_type" :value="__('Facility Type')" />
                        <select id="facility_type" name="facility_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @foreach (\App\Models\Facility::TYPES as $t)
                                <option value="{{ $t }}" @selected(old('facility_type') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_type')" />
                    </div>

                    {{-- Location (Rwanda): Province → District → Sector → Cell → Village --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4" x-data="locationDropdowns()" x-init="loadCountries()">
                        <input type="hidden" name="country_id" :value="countryId || ''">
                        <input type="hidden" name="province_id" :value="provinceId || ''">
                        <input type="hidden" name="district_id" :value="districtId || ''">
                        <input type="hidden" name="sector_id" :value="sectorId || ''">
                        <input type="hidden" name="cell_id" :value="cellId || ''">
                        <input type="hidden" name="village_id" :value="villageId || ''">
                        <h3 class="text-base font-semibold text-slate-800">{{ __('Location (Rwanda)') }}</h3>
                        <p class="text-sm text-slate-500">{{ __('Select Province, District, Sector, Cell and Village.') }}</p>
                        <div>
                            <x-input-label for="country_id" :value="__('Country')" />
                            <select id="country_id" x-model="countryId" @change="onCountryChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                                <option value="">{{ __('Select country') }}</option>
                                <template x-for="d in countries" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="province_id" :value="__('Province')" />
                            <select id="province_id" x-model="provinceId" @change="onProvinceChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 shadow-sm" :disabled="!countryId">
                                <option value="">{{ __('Select province') }}</option>
                                <template x-for="d in provinces" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="district_id" :value="__('District')" />
                            <select id="district_id" x-model="districtId" @change="onDistrictChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 shadow-sm" :disabled="!provinceId">
                                <option value="">{{ __('Select district') }}</option>
                                <template x-for="d in districts" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="sector_id" :value="__('Sector')" />
                            <select id="sector_id" x-model="sectorId" @change="onSectorChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 shadow-sm" :disabled="!districtId">
                                <option value="">{{ __('Select sector') }}</option>
                                <template x-for="d in sectors" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="cell_id" :value="__('Cell')" />
                            <select id="cell_id" x-model="cellId" @change="onCellChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 shadow-sm" :disabled="!sectorId">
                                <option value="">{{ __('Select cell') }}</option>
                                <template x-for="d in cells" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="village_id" :value="__('Village')" />
                            <select id="village_id" x-model="villageId" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 shadow-sm" :disabled="!cellId">
                                <option value="">{{ __('Select village') }}</option>
                                <template x-for="d in villages" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="gps" :value="__('GPS Coordinates')" />
                        <x-text-input id="gps" name="gps" type="text" class="mt-1 block w-full" :value="old('gps')" placeholder="e.g. -1.9536, 30.0606" />
                        <x-input-error class="mt-2" :messages="$errors->get('gps')" />
                    </div>

                    <div>
                        <x-input-label for="license_number" :value="__('License Number')" />
                        <x-text-input id="license_number" name="license_number" type="text" class="mt-1 block w-full" :value="old('license_number')" />
                        <x-input-error class="mt-2" :messages="$errors->get('license_number')" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="license_issue_date" :value="__('License Issue Date')" />
                            <x-text-input id="license_issue_date" name="license_issue_date" type="date" class="mt-1 block w-full" :value="old('license_issue_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('license_issue_date')" />
                        </div>
                        <div>
                            <x-input-label for="license_expiry_date" :value="__('License Expiry Date')" />
                            <x-text-input id="license_expiry_date" name="license_expiry_date" type="date" class="mt-1 block w-full" :value="old('license_expiry_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('license_expiry_date')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="daily_capacity" :value="__('Daily Production Capacity')" />
                        <x-text-input id="daily_capacity" name="daily_capacity" type="number" min="0" class="mt-1 block w-full" :value="old('daily_capacity')" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('e.g. animals per day or kg') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('daily_capacity')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach (\App\Models\Facility::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', 'active') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Add Facility') }}</x-primary-button>
                        <a href="{{ route('businesses.facilities.index', $business) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function locationDropdowns() {
            const baseUrl = '{{ route("divisions.index") }}';
            return {
                countries: [], provinces: [], districts: [], sectors: [], cells: [], villages: [],
                countryId: '{{ old("country_id") }}' || '',
                provinceId: '{{ old("province_id") }}' || '',
                districtId: '{{ old("district_id") }}' || '',
                sectorId: '{{ old("sector_id") }}' || '',
                cellId: '{{ old("cell_id") }}' || '',
                villageId: '{{ old("village_id") }}' || '',
                async fetchChildren(parentId) {
                    try {
                        const url = parentId ? `${baseUrl}?parent_id=${parentId}` : baseUrl;
                        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
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
</x-app-layout>
