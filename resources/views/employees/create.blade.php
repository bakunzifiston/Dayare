<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">
            {{ __('Add employee') }}
        </h1>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('employees.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="business_id" :value="__('Business')" />
                        <select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('Select business') }}</option>
                            @foreach ($businesses as $business)
                                <option value="{{ $business->id }}" @selected(old('business_id') == $business->id)>{{ $business->business_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('business_id')" />
                    </div>
                    <div>
                        <x-input-label for="facility_id" :value="__('Facility (optional)')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('Select facility') }}</option>
                            @foreach ($businesses as $business)
                                @foreach ($business->facilities as $facility)
                                    <option value="{{ $facility->id }}" @selected(old('facility_id') == $facility->id)">
                                        {{ $business->business_name }} – {{ $facility->facility_name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                    <div>
                        <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                        <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="national_id" :value="__('National ID')" />
                        <x-text-input id="national_id" name="national_id" type="text" class="mt-1 block w-full" :value="old('national_id')" />
                        <x-input-error class="mt-2" :messages="$errors->get('national_id')" />
                    </div>
                    <div>
                        <x-input-label for="nationality" :value="__('Nationality')" />
                        <x-text-input id="nationality" name="nationality" type="text" class="mt-1 block w-full" :value="old('nationality')" />
                        <x-input-error class="mt-2" :messages="$errors->get('nationality')" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="job_title" :value="__('Job title')" />
                        <select id="job_title" name="job_title" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('Select job title (optional)') }}</option>
                            @foreach (\App\Models\Employee::JOB_TITLES as $value => $label)
                                <option value="{{ $value }}" @selected(old('job_title') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
                    </div>
                    <div>
                        <x-input-label for="employment_type" :value="__('Employment type')" />
                        <select id="employment_type" name="employment_type" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                            @foreach (['full_time', 'part_time', 'contractor', 'casual'] as $type)
                                <option value="{{ $type }}" @selected(old('employment_type', 'full_time') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('employment_type')" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="hire_date" :value="__('Hire date')" />
                        <x-text-input id="hire_date" name="hire_date" type="date" class="mt-1 block w-full" :value="old('hire_date')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('hire_date')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                            @foreach (['active', 'probation', 'on_leave', 'terminated'] as $status)
                                <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="work_email" :value="__('Work email')" />
                        <x-text-input id="work_email" name="work_email" type="email" class="mt-1 block w-full" :value="old('work_email')" />
                        <x-input-error class="mt-2" :messages="$errors->get('work_email')" />
                    </div>
                    <div>
                        <x-input-label for="personal_email" :value="__('Personal email')" />
                        <x-text-input id="personal_email" name="personal_email" type="email" class="mt-1 block w-full" :value="old('personal_email')" />
                        <x-input-error class="mt-2" :messages="$errors->get('personal_email')" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                </div>

                {{-- Address: country → province → district → sector → cell → village --}}
                <div class="mt-6 border-t border-slate-200 pt-4" x-data="employeeLocationDropdowns()" x-init="loadCountries()">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3">{{ __('Home address (optional)') }}</h3>

                    <input type="hidden" name="country_id" :value="countryId || ''">
                    <input type="hidden" name="province_id" :value="provinceId || ''">
                    <input type="hidden" name="district_id" :value="districtId || ''">
                    <input type="hidden" name="sector_id" :value="sectorId || ''">
                    <input type="hidden" name="cell_id" :value="cellId || ''">
                    <input type="hidden" name="village_id" :value="villageId || ''">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                            <select id="province_id" x-model="provinceId" @change="onProvinceChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm" :disabled="!countryId">
                                <option value="">{{ __('Select province') }}</option>
                                <template x-for="d in provinces" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('province_id')" />
                        </div>
                        <div>
                            <x-input-label for="district_id" :value="__('District')" />
                            <select id="district_id" x-model="districtId" @change="onDistrictChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm" :disabled="!provinceId">
                                <option value="">{{ __('Select district') }}</option>
                                <template x-for="d in districts" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('district_id')" />
                        </div>
                        <div>
                            <x-input-label for="sector_id" :value="__('Sector')" />
                            <select id="sector_id" x-model="sectorId" @change="onSectorChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm" :disabled="!districtId">
                                <option value="">{{ __('Select sector') }}</option>
                                <template x-for="d in sectors" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('sector_id')" />
                        </div>
                        <div>
                            <x-input-label for="cell_id" :value="__('Cell')" />
                            <select id="cell_id" x-model="cellId" @change="onCellChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm" :disabled="!sectorId">
                                <option value="">{{ __('Select cell') }}</option>
                                <template x-for="d in cells" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('cell_id')" />
                        </div>
                        <div>
                            <x-input-label for="village_id" :value="__('Village')" />
                            <select id="village_id" x-model="villageId" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm" :disabled="!cellId">
                                <option value="">{{ __('Select village') }}</option>
                                <template x-for="d in villages" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('village_id')" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('employees.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-bucha-primary text-white text-sm font-medium hover:bg-bucha-burgundy">
                    {{ __('Save employee') }}
                </button>
            </div>
        </form>
    </div>

    <script>
        function employeeLocationDropdowns() {
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
                    } catch (e) {
                        return [];
                    }
                },
                async loadCountries() {
                    try {
                        this.countries = await this.fetchChildren(null);
                        await this.restoreCascade();
                    } catch (e) {
                        this.countries = [];
                    }
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
                },
            };
        }
    </script>
</x-app-layout>

