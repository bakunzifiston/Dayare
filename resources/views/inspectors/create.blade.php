<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Register Inspector') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('inspectors.store') }}" class="space-y-8" id="inspector-create-form" onsubmit="var f=document.getElementById('inspector-create-form');if(f){['country_id','province_id','district_id','sector_id','cell_id','village_id'].forEach(function(id){var s=document.getElementById(id),h=f.querySelector('input[name='+id+']');if(s&&h)h.value=s?s.value:'';});}return true;">
                    @csrf

                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Assigned Facility') }}</h3>
                        <div>
                            <x-input-label for="facility_id" :value="__('Facility')" />
                            <select id="facility_id" name="facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                                <option value="">{{ __('Select facility') }}</option>
                                @foreach ($facilities as $f)
                                    <option value="{{ $f['id'] }}" @selected(old('facility_id') == $f['id'])>{{ $f['label'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Personal information') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="first_name" :value="__('First name')" />
                                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                            </div>
                            <div>
                                <x-input-label for="last_name" :value="__('Last name')" />
                                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="national_id" :value="__('National ID')" />
                            <x-text-input id="national_id" name="national_id" type="text" class="mt-1 block w-full" :value="old('national_id')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('national_id')" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="phone_number" :value="__('Phone number')" />
                                <x-text-input id="phone_number" name="phone_number" type="text" class="mt-1 block w-full" :value="old('phone_number')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
                            </div>
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="dob" :value="__('Date of birth')" />
                                <x-text-input id="dob" name="dob" type="date" class="mt-1 block w-full" :value="old('dob')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('dob')" />
                            </div>
                            <div>
                                <x-input-label for="nationality" :value="__('Nationality')" />
                                <x-text-input id="nationality" name="nationality" type="text" class="mt-1 block w-full" :value="old('nationality')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('nationality')" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4" x-data="locationDropdowns()" x-init="loadCountries()">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Location') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('Select Country, Province, District, Sector, Cell and Village.') }}</p>
                        <input type="hidden" name="country_id" :value="countryId || ''">
                        <input type="hidden" name="province_id" :value="provinceId || ''">
                        <input type="hidden" name="district_id" :value="districtId || ''">
                        <input type="hidden" name="sector_id" :value="sectorId || ''">
                        <input type="hidden" name="cell_id" :value="cellId || ''">
                        <input type="hidden" name="village_id" :value="villageId || ''">
                        <div>
                            <x-input-label for="country_id" :value="__('Country')" />
                            <select id="country_id" x-model="countryId" @change="onCountryChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                <option value="">{{ __('Select country') }}</option>
                                <template x-for="d in countries" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('country_id')" />
                        </div>
                        <div>
                            <x-input-label for="province_id" :value="__('Province')" />
                            <select id="province_id" x-model="provinceId" @change="onProvinceChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary shadow-sm" :disabled="!countryId">
                                <option value="">{{ __('Select province') }}</option>
                                <template x-for="d in provinces" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('province_id')" />
                        </div>
                        <div>
                            <x-input-label for="district_id" :value="__('District')" />
                            <select id="district_id" x-model="districtId" @change="onDistrictChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary shadow-sm" :disabled="!provinceId">
                                <option value="">{{ __('Select district') }}</option>
                                <template x-for="d in districts" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('district_id')" />
                        </div>
                        <div>
                            <x-input-label for="sector_id" :value="__('Sector')" />
                            <select id="sector_id" x-model="sectorId" @change="onSectorChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary shadow-sm" :disabled="!districtId">
                                <option value="">{{ __('Select sector') }}</option>
                                <template x-for="d in sectors" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('sector_id')" />
                        </div>
                        <div>
                            <x-input-label for="cell_id" :value="__('Cell')" />
                            <select id="cell_id" x-model="cellId" @change="onCellChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary shadow-sm" :disabled="!sectorId">
                                <option value="">{{ __('Select cell') }}</option>
                                <template x-for="d in cells" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('cell_id')" />
                        </div>
                        <div>
                            <x-input-label for="village_id" :value="__('Village')" />
                            <select id="village_id" x-model="villageId" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary shadow-sm" :disabled="!cellId">
                                <option value="">{{ __('Select village') }}</option>
                                <template x-for="d in villages" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('village_id')" />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Authorization') }}</h3>
                        <div>
                            <x-input-label for="authorization_number" :value="__('Authorization number')" />
                            <x-text-input id="authorization_number" name="authorization_number" type="text" class="mt-1 block w-full" :value="old('authorization_number')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('authorization_number')" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="authorization_issue_date" :value="__('Authorization issue date')" />
                                <x-text-input id="authorization_issue_date" name="authorization_issue_date" type="date" class="mt-1 block w-full" :value="old('authorization_issue_date')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('authorization_issue_date')" />
                            </div>
                            <div>
                                <x-input-label for="authorization_expiry_date" :value="__('Authorization expiry date')" />
                                <x-text-input id="authorization_expiry_date" name="authorization_expiry_date" type="date" class="mt-1 block w-full" :value="old('authorization_expiry_date')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('authorization_expiry_date')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="species_allowed" :value="__('Species allowed')" />
                            @if (($species ?? collect())->isEmpty())
                                <p class="mt-1 text-sm text-amber-600">{{ __('No species configured yet. Add species in') }} <a href="{{ route('species.index') }}" class="underline">{{ __('Settings → Species') }}</a>.</p>
                            @else
                                <select id="species_allowed" name="species_allowed[]" multiple class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm" size="{{ max(3, min(8, $species->count() + 1)) }}">
                                    @foreach ($species as $s)
                                        <option value="{{ $s->name }}" @selected(in_array($s->name, old('species_allowed', [])))>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">{{ __('Hold Ctrl/Cmd to select multiple species.') }}</p>
                            @endif
                            <x-input-error class="mt-2" :messages="$errors->get('species_allowed')" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="daily_capacity" :value="__('Daily capacity')" />
                                <x-text-input id="daily_capacity" name="daily_capacity" type="number" min="0" class="mt-1 block w-full" :value="old('daily_capacity')" />
                                <x-input-error class="mt-2" :messages="$errors->get('daily_capacity')" />
                            </div>
                            <div>
                                <x-input-label for="stamp_serial_number" :value="__('Stamp serial number')" />
                                <x-text-input id="stamp_serial_number" name="stamp_serial_number" type="text" class="mt-1 block w-full" :value="old('stamp_serial_number')" />
                                <x-input-error class="mt-2" :messages="$errors->get('stamp_serial_number')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                                @foreach (\App\Models\Inspector::STATUSES as $s)
                                    <option value="{{ $s }}" @selected(old('status', 'active') === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Register Inspector') }}</x-primary-button>
                        <a href="{{ route('inspectors.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
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
