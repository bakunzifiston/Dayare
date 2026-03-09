<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">
            {{ __('Add client') }}
        </h1>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('clients.store') }}" class="space-y-6" id="client-create-form" onsubmit="var f=document.getElementById('client-create-form');if(f){['country_id','province_id','district_id','sector_id','cell_id','village_id'].forEach(function(id){var s=document.getElementById(id),h=f.querySelector('input[name='+id+']');if(s&&h)h.value=s?s.value:'';});}return true;">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Basic information') }}</h2>
                <div>
                    <x-input-label for="business_id" :value="__('Business')" />
                    <select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                        <option value="">{{ __('Select business') }}</option>
                        @foreach ($businesses as $b)
                            <option value="{{ $b->id }}" @selected(old('business_id') == $b->id)>{{ $b->business_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('business_id')" />
                </div>
                <div>
                    <x-input-label for="name" :value="__('Client name / Company name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="contact_person" :value="__('Contact person (optional)')" />
                    <x-text-input id="contact_person" name="contact_person" type="text" class="mt-1 block w-full" :value="old('contact_person')" />
                    <x-input-error class="mt-2" :messages="$errors->get('contact_person')" />
                </div>
                <div class="flex items-center gap-2">
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-[#3B82F6] focus:ring-[#3B82F6]" @checked(old('is_active', true)) />
                    <label for="is_active" class="text-sm text-slate-700">{{ __('Active') }}</label>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Contact') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4" x-data="locationDropdowns()" x-init="loadCountries()">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Location') }}</h2>
                <p class="text-sm text-slate-500">{{ __('Select Country, Province, District, Sector, Cell and Village.') }}</p>
                <input type="hidden" name="country_id" :value="countryId || ''">
                <input type="hidden" name="province_id" :value="provinceId || ''">
                <input type="hidden" name="district_id" :value="districtId || ''">
                <input type="hidden" name="sector_id" :value="sectorId || ''">
                <input type="hidden" name="cell_id" :value="cellId || ''">
                <input type="hidden" name="village_id" :value="villageId || ''">
                <div>
                    <x-input-label for="country_id" :value="__('Country')" />
                    <select id="country_id" x-model="countryId" @change="onCountryChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6] shadow-sm">
                        <option value="">{{ __('Select country') }}</option>
                        <template x-for="d in countries" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('country_id')" />
                </div>
                <div>
                    <x-input-label for="province_id" :value="__('Province')" />
                    <select id="province_id" x-model="provinceId" @change="onProvinceChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6] shadow-sm" :disabled="!countryId">
                        <option value="">{{ __('Select province') }}</option>
                        <template x-for="d in provinces" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('province_id')" />
                </div>
                <div>
                    <x-input-label for="district_id" :value="__('District')" />
                    <select id="district_id" x-model="districtId" @change="onDistrictChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6] shadow-sm" :disabled="!provinceId">
                        <option value="">{{ __('Select district') }}</option>
                        <template x-for="d in districts" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('district_id')" />
                </div>
                <div>
                    <x-input-label for="sector_id" :value="__('Sector')" />
                    <select id="sector_id" x-model="sectorId" @change="onSectorChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6] shadow-sm" :disabled="!districtId">
                        <option value="">{{ __('Select sector') }}</option>
                        <template x-for="d in sectors" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('sector_id')" />
                </div>
                <div>
                    <x-input-label for="cell_id" :value="__('Cell')" />
                    <select id="cell_id" x-model="cellId" @change="onCellChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6] shadow-sm" :disabled="!sectorId">
                        <option value="">{{ __('Select cell') }}</option>
                        <template x-for="d in cells" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('cell_id')" />
                </div>
                <div>
                    <x-input-label for="village_id" :value="__('Village')" />
                    <select id="village_id" x-model="villageId" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6] shadow-sm" :disabled="!cellId">
                        <option value="">{{ __('Select village') }}</option>
                        <template x-for="d in villages" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('village_id')" />
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Optional') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tax_id" :value="__('Tax ID')" />
                        <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full" :value="old('tax_id')" />
                        <x-input-error class="mt-2" :messages="$errors->get('tax_id')" />
                    </div>
                    <div>
                        <x-input-label for="registration_number" :value="__('Registration number')" />
                        <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full" :value="old('registration_number')" />
                        <x-input-error class="mt-2" :messages="$errors->get('registration_number')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="business_type" :value="__('Business type')" />
                        <select id="business_type" name="business_type" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                            <option value="">{{ __('None') }}</option>
                            @foreach (\App\Models\Client::BUSINESS_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('business_type') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="preferred_facility_id" :value="__('Preferred facility')" />
                        <select id="preferred_facility_id" name="preferred_facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($facilities ?? [] as $f)
                                <option value="{{ $f->id }}" @selected(old('preferred_facility_id') == $f->id)>{{ $f->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="preferred_species" :value="__('Preferred species')" />
                    <x-text-input id="preferred_species" name="preferred_species" type="text" class="mt-1 block w-full" :value="old('preferred_species')" placeholder="e.g. Cattle, Goat" />
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('notes') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Create client') }}
                </button>
                <a href="{{ route('clients.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
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
