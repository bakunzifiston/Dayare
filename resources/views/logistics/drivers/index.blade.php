@php
    $driverFieldKeys = [
        'first_name',
        'last_name',
        'phone_number',
        'national_id_or_license_id',
        'gender',
        'dob',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'photo',
        'license_number',
        'license_category',
        'license_expiry',
        'experience_years',
    ];
    $showDriverForm = collect($driverFieldKeys)->contains(fn ($key) => old($key) !== null || $errors->has($key));
@endphp

@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    <div class="space-y-4">
        <form method="GET" action="{{ route('logistics.drivers.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Company') }}</label>
            <div class="flex gap-2">
                <select name="company_id" class="w-full rounded-md border-slate-300 text-sm">
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === (int) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-[#334155] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1e293b]">{{ __('Load') }}</button>
            </div>
        </form>

        <section class="rounded-lg border border-slate-200 bg-white p-4" x-data="{ showDriverForm: @js($showDriverForm) }">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Drivers') }}</h2>
                <button
                    type="button"
                    class="rounded-md bg-[#7A1C22] px-3 py-2 text-xs font-semibold text-white hover:bg-[#64161c]"
                    x-on:click="showDriverForm = true; $nextTick(() => document.getElementById('driver-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                >
                    {{ __('Add driver') }}
                </button>
            </div>
            <div
                id="driver-form"
                x-show="showDriverForm"
                x-transition
            >
                <div
                    x-data="locationDropdownsLogisticsDriver()"
                    x-init="loadCountries()"
                >
                    <form
                        method="POST"
                        action="{{ route('logistics.drivers.store') }}"
                        enctype="multipart/form-data"
                        class="grid gap-2 md:grid-cols-2"
                    >
                        @csrf
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                        <input name="first_name" value="{{ old('first_name') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('First name') }}" required>
                        <input name="last_name" value="{{ old('last_name') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Last name') }}" required>
                        <input name="phone_number" value="{{ old('phone_number') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Phone number (max 10 digits)') }}" maxlength="10" inputmode="numeric" required>
                        <input name="national_id_or_license_id" value="{{ old('national_id_or_license_id') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('National ID / License ID') }}" required>
                        <select name="gender" class="rounded-md border-slate-300 text-sm" required>
                            <option value="">{{ __('Select gender') }}</option>
                            <option value="female" @selected(old('gender') === 'female')>{{ __('Female') }}</option>
                            <option value="male" @selected(old('gender') === 'male')>{{ __('Male') }}</option>
                        </select>
                        <input type="date" name="dob" value="{{ old('dob') }}" class="rounded-md border-slate-300 text-sm" required>
                        <input name="license_number" value="{{ old('license_number') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Driving license number') }}" required>
                        <input name="license_category" value="{{ old('license_category') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('License category') }}" required>
                        <input type="date" name="license_expiry" value="{{ old('license_expiry') }}" class="rounded-md border-slate-300 text-sm" required>
                        <input type="number" min="0" max="80" name="experience_years" value="{{ old('experience_years') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Experience (years)') }}" required>
                        <div class="md:col-span-2">
                            <p class="mb-1 text-xs font-medium text-slate-500">{{ __('Location (Rwanda)') }}</p>
                        </div>
                        <input type="hidden" name="country_id" :value="countryId || ''">
                        <input type="hidden" name="province_id" :value="provinceId || ''">
                        <input type="hidden" name="district_id" :value="districtId || ''">
                        <input type="hidden" name="sector_id" :value="sectorId || ''">
                        <input type="hidden" name="cell_id" :value="cellId || ''">
                        <input type="hidden" name="village_id" :value="villageId || ''">
                        <select x-model="provinceId" @change="onProvinceChange()" class="rounded-md border-slate-300 text-sm" :disabled="!countryId">
                            <option value="">{{ __('Select province') }}</option>
                            <template x-for="d in provinces" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <select x-model="districtId" @change="onDistrictChange()" class="rounded-md border-slate-300 text-sm" :disabled="!provinceId">
                            <option value="">{{ __('Select district') }}</option>
                            <template x-for="d in districts" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <select x-model="sectorId" @change="onSectorChange()" class="rounded-md border-slate-300 text-sm" :disabled="!districtId">
                            <option value="">{{ __('Select sector') }}</option>
                            <template x-for="d in sectors" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <select x-model="cellId" @change="onCellChange()" class="rounded-md border-slate-300 text-sm" :disabled="!sectorId">
                            <option value="">{{ __('Select cell') }}</option>
                            <template x-for="d in cells" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <select x-model="villageId" class="rounded-md border-slate-300 text-sm md:col-span-2" :disabled="!cellId">
                            <option value="">{{ __('Select village') }}</option>
                            <template x-for="d in villages" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Photo (optional)') }}</label>
                            <input type="file" name="photo" accept="image/*" class="block w-full rounded-md border border-slate-300 bg-white px-2 py-2 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save driver') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="mt-4">
                <x-logistics.table
                    :columns="[__('Photo'), __('Driver'), __('Phone'), __('Driving license'), __('Status')]"
                    :has-rows="$drivers->isNotEmpty()"
                    :empty-message="__('No drivers found')"
                >
                    @foreach ($drivers as $driver)
                        <tr>
                            <td class="px-4 py-3">
                                @if ($driver->photo_path)
                                    <img src="{{ asset('storage/'.$driver->photo_path) }}" alt="{{ $driver->name }}" class="h-9 w-9 rounded-full object-cover">
                                @else
                                    <div class="h-9 w-9 rounded-full bg-slate-100"></div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $driver->name }}</td>
                            <td class="px-4 py-3">{{ $driver->phone_number ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $driver->license_number }}</td>
                            <td class="px-4 py-3"><x-logistics.status-badge :status="$driver->status" /></td>
                        </tr>
                    @endforeach
                </x-logistics.table>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            function locationDropdownsLogisticsDriver() {
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
                            const res = await fetch(url, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin',
                            });
                            const data = await res.json();
                            return Array.isArray(data) ? data : [];
                        } catch (e) {
                            return [];
                        }
                    },
                    async loadCountries() {
                        this.countries = await this.fetchChildren(null);
                        if (!this.countryId) {
                            const rwanda = this.countries.find((country) => String(country.name || '').toLowerCase() === 'rwanda');
                            this.countryId = rwanda ? String(rwanda.id) : '';
                        }
                        await this.restoreCascade();
                    },
                    async restoreCascade() {
                        if (!this.countryId) return;
                        this.provinces = await this.fetchChildren(this.countryId);
                        if (this.provinceId) {
                            this.districts = await this.fetchChildren(this.provinceId);
                        }
                        if (this.districtId) {
                            this.sectors = await this.fetchChildren(this.districtId);
                        }
                        if (this.sectorId) {
                            this.cells = await this.fetchChildren(this.sectorId);
                        }
                        if (this.cellId) {
                            this.villages = await this.fetchChildren(this.cellId);
                        }
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
    @endpush
@endcomponent
