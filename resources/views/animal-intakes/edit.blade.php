<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('Edit animal intake') }}</h2>
            <a href="{{ route('animal-intakes.show', $intake) }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Back to intake') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('animal-intakes.update', $intake) }}" class="space-y-6" id="animal-intake-edit-form" onsubmit="var f=document.getElementById('animal-intake-edit-form');if(f){['country_id','province_id','district_id','sector_id','cell_id','village_id'].forEach(function(id){var s=document.getElementById(id),h=f.querySelector('input[name='+id+']');if(s&&h)h.value=s?s.value:'';});}return true;">
                @csrf
                @method('patch')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4">
                    <h3 class="text-base font-semibold text-slate-800">{{ __('Facility & date') }}</h3>
                    <div>
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(old('facility_id', $intake->facility_id) == $f->id)>{{ $f->facility_name }} ({{ $f->facility_type }})</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>
                    <div>
                        <x-input-label for="intake_date" :value="__('Intake date')" />
                        <x-text-input id="intake_date" name="intake_date" type="date" class="mt-1 block w-full" :value="old('intake_date', $intake->intake_date->format('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('intake_date')" />
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4">
                    <h3 class="text-base font-semibold text-slate-800">{{ __('Supplier & farm') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="supplier_firstname" :value="__('Supplier first name')" />
                            <x-text-input id="supplier_firstname" name="supplier_firstname" type="text" class="mt-1 block w-full" :value="old('supplier_firstname', $intake->supplier_firstname)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('supplier_firstname')" />
                        </div>
                        <div>
                            <x-input-label for="supplier_lastname" :value="__('Supplier last name')" />
                            <x-text-input id="supplier_lastname" name="supplier_lastname" type="text" class="mt-1 block w-full" :value="old('supplier_lastname', $intake->supplier_lastname)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('supplier_lastname')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="supplier_contact" :value="__('Supplier contact')" />
                        <x-text-input id="supplier_contact" name="supplier_contact" type="text" class="mt-1 block w-full" :value="old('supplier_contact', $intake->supplier_contact)" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="farm_name" :value="__('Farm name')" />
                            <x-text-input id="farm_name" name="farm_name" type="text" class="mt-1 block w-full" :value="old('farm_name', $intake->farm_name)" />
                        </div>
                        <div>
                            <x-input-label for="farm_registration_number" :value="__('Farm registration number')" />
                            <x-text-input id="farm_registration_number" name="farm_registration_number" type="text" class="mt-1 block w-full" :value="old('farm_registration_number', $intake->farm_registration_number)" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4" x-data="locationDropdownsEdit()" x-init="loadCountries()">
                    <input type="hidden" name="country_id" :value="countryId || ''">
                    <input type="hidden" name="province_id" :value="provinceId || ''">
                    <input type="hidden" name="district_id" :value="districtId || ''">
                    <input type="hidden" name="sector_id" :value="sectorId || ''">
                    <input type="hidden" name="cell_id" :value="cellId || ''">
                    <input type="hidden" name="village_id" :value="villageId || ''">
                    <h3 class="text-base font-semibold text-slate-800">{{ __('Origin location') }}</h3>
                    <div>
                        <x-input-label for="country_id" :value="__('Country')" />
                        <select id="country_id" x-model="countryId" @change="onCountryChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 shadow-sm">
                            <option value="">{{ __('Select country') }}</option>
                            <template x-for="d in countries" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="province_id" :value="__('Province')" />
                        <select id="province_id" x-model="provinceId" @change="onProvinceChange()" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" :disabled="!countryId">
                            <option value="">{{ __('Select province') }}</option>
                            <template x-for="d in provinces" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="district_id" :value="__('District')" />
                        <select id="district_id" x-model="districtId" @change="onDistrictChange()" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" :disabled="!provinceId">
                            <option value="">{{ __('Select district') }}</option>
                            <template x-for="d in districts" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="sector_id" :value="__('Sector')" />
                        <select id="sector_id" x-model="sectorId" @change="onSectorChange()" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" :disabled="!districtId">
                            <option value="">{{ __('Select sector') }}</option>
                            <template x-for="d in sectors" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="cell_id" :value="__('Cell')" />
                        <select id="cell_id" x-model="cellId" @change="onCellChange()" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" :disabled="!sectorId">
                            <option value="">{{ __('Select cell') }}</option>
                            <template x-for="d in cells" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="village_id" :value="__('Village')" />
                        <select id="village_id" x-model="villageId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" :disabled="!cellId">
                            <option value="">{{ __('Select village') }}</option>
                            <template x-for="d in villages" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4">
                    <h3 class="text-base font-semibold text-slate-800">{{ __('Animals & pricing') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="species" :value="__('Species')" />
                            <select id="species" name="species" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                                @foreach (\App\Models\AnimalIntake::SPECIES_OPTIONS as $s)
                                    <option value="{{ $s }}" @selected(old('species', $intake->species) === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('species')" />
                        </div>
                        <div>
                            <x-input-label for="number_of_animals" :value="__('Number of animals')" />
                            <x-text-input id="number_of_animals" name="number_of_animals" type="number" min="1" class="mt-1 block w-full" :value="old('number_of_animals', $intake->number_of_animals)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('number_of_animals')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="unit_price" :value="__('Unit price')" />
                            <x-text-input id="unit_price" name="unit_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('unit_price', $intake->unit_price)" />
                        </div>
                        <div>
                            <x-input-label for="total_price" :value="__('Total price')" />
                            <x-text-input id="total_price" name="total_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('total_price', $intake->total_price)" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="animal_identification_numbers" :value="__('Animal identification numbers')" />
                        <textarea id="animal_identification_numbers" name="animal_identification_numbers" rows="2" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 rounded-md shadow-sm">{{ old('animal_identification_numbers', $intake->animal_identification_numbers) }}</textarea>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4">
                    <h3 class="text-base font-semibold text-slate-800">{{ __('Transport') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="transport_vehicle_plate" :value="__('Vehicle plate')" />
                            <x-text-input id="transport_vehicle_plate" name="transport_vehicle_plate" type="text" class="mt-1 block w-full" :value="old('transport_vehicle_plate', $intake->transport_vehicle_plate)" />
                        </div>
                        <div>
                            <x-input-label for="driver_name" :value="__('Driver name')" />
                            <x-text-input id="driver_name" name="driver_name" type="text" class="mt-1 block w-full" :value="old('driver_name', $intake->driver_name)" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4">
                    <h3 class="text-base font-semibold text-slate-800">{{ __('Health certificate') }}</h3>
                    <div>
                        <x-input-label for="animal_health_certificate_number" :value="__('Health certificate number')" />
                        <x-text-input id="animal_health_certificate_number" name="animal_health_certificate_number" type="text" class="mt-1 block w-full" :value="old('animal_health_certificate_number', $intake->animal_health_certificate_number)" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="health_certificate_issue_date" :value="__('Issue date')" />
                            <x-text-input id="health_certificate_issue_date" name="health_certificate_issue_date" type="date" class="mt-1 block w-full" :value="old('health_certificate_issue_date', $intake->health_certificate_issue_date?->format('Y-m-d'))" />
                        </div>
                        <div>
                            <x-input-label for="health_certificate_expiry_date" :value="__('Expiry date')" />
                            <x-text-input id="health_certificate_expiry_date" name="health_certificate_expiry_date" type="date" class="mt-1 block w-full" :value="old('health_certificate_expiry_date', $intake->health_certificate_expiry_date?->format('Y-m-d'))" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4">
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            @foreach (['received' => __('Received'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $intake->status) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Update') }}</button>
                    <a href="{{ route('animal-intakes.show', $intake) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function locationDropdownsEdit() {
            const baseUrl = '{{ route("divisions.index") }}';
            return {
                countries: [], provinces: [], districts: [], sectors: [], cells: [], villages: [],
                countryId: '{{ old("country_id", $intake->country_id) }}' || '',
                provinceId: '{{ old("province_id", $intake->province_id) }}' || '',
                districtId: '{{ old("district_id", $intake->district_id) }}' || '',
                sectorId: '{{ old("sector_id", $intake->sector_id) }}' || '',
                cellId: '{{ old("cell_id", $intake->cell_id) }}' || '',
                villageId: '{{ old("village_id", $intake->village_id) }}' || '',
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
