@php
    $site = $site ?? null;
    $type = old('site_type', $site?->site_type ?? \App\Support\SalesComplianceCatalog::SITE_RESTAURANT);
    $locationCountryId = (string) old('country_id', $site?->country_id ?? '');
    $locationProvinceId = (string) old('province_id', $site?->province_id ?? '');
    $locationDistrictId = (string) old('district_id', $site?->district_id ?? '');
    $locationSectorId = (string) old('sector_id', $site?->sector_id ?? '');
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="site_type" :value="__('Site type')" />
        <select id="site_type" name="site_type" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
            @foreach (\App\Support\SalesComplianceCatalog::siteTypeLabels() as $value => $label)
                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('site_type')" />
    </div>
    <div>
        <x-input-label for="name" :value="__('Site name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('name', $site?->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>
    <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2" x-data="salesComplianceLocationDropdowns()" x-init="loadCountries()">
        <input type="hidden" name="country_id" :value="countryId || ''">
        <input type="hidden" name="province_id" :value="provinceId || ''">
        <input type="hidden" name="district_id" :value="districtId || ''">
        <input type="hidden" name="sector_id" :value="sectorId || ''">
        <div>
            <x-input-label for="country_id" :value="__('Country')" />
            <select id="country_id" x-model="countryId" @change="onCountryChange()" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm">
                <option value="">{{ __('Select country') }}</option>
                <template x-for="d in countries" :key="d.id">
                    <option :value="String(d.id)" x-text="d.name"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('country_id')" />
        </div>
        <div>
            <x-input-label for="province_id" :value="__('Province')" />
            <select id="province_id" x-model="provinceId" @change="onProvinceChange()" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :disabled="!countryId">
                <option value="">{{ __('Select province') }}</option>
                <template x-for="d in provinces" :key="d.id">
                    <option :value="String(d.id)" x-text="d.name"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('province_id')" />
        </div>
        <div>
            <x-input-label for="district_id" :value="__('District')" />
            <select id="district_id" x-model="districtId" @change="onDistrictChange()" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :disabled="!provinceId">
                <option value="">{{ __('Select district') }}</option>
                <template x-for="d in districts" :key="d.id">
                    <option :value="String(d.id)" x-text="d.name"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('district_id')" />
        </div>
        <div>
            <x-input-label for="sector_id" :value="__('Sector')" />
            <select id="sector_id" x-model="sectorId" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :disabled="!districtId">
                <option value="">{{ __('Select sector') }}</option>
                <template x-for="d in sectors" :key="d.id">
                    <option :value="String(d.id)" x-text="d.name"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('sector_id')" />
        </div>
    </div>
</div>

<div id="event-fields" class="mt-4 grid gap-4 sm:grid-cols-2 {{ $type === \App\Support\SalesComplianceCatalog::SITE_PRIVATE_EVENT ? '' : 'hidden' }}">
    <div>
        <x-input-label for="event_type" :value="__('Event type')" />
        <select id="event_type" name="event_type" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm">
            <option value="">{{ __('Select event type') }}</option>
            @foreach (['wedding' => __('Wedding'), 'private_party' => __('Private party'), 'funeral' => __('Funeral'), 'corporate' => __('Corporate'), 'other' => __('Other')] as $value => $label)
                <option value="{{ $value }}" @selected(old('event_type', $site?->event_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('event_type')" />
    </div>
    <div>
        <x-input-label for="event_name" :value="__('Event name')" />
        <x-text-input id="event_name" name="event_name" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('event_name', $site?->event_name)" placeholder="{{ __('e.g. Uwase wedding') }}" />
        <x-input-error class="mt-2" :messages="$errors->get('event_name')" />
    </div>
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-3">
    <div>
        <x-input-label for="contact_name" :value="__('Contact person')" />
        <x-text-input id="contact_name" name="contact_name" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('contact_name', $site?->contact_name)" />
        <p id="contact-hint" class="mt-1 text-xs text-slate-500">{{ __('Required for restaurants, bars, and butcheries.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('contact_name')" />
    </div>
    <div>
        <x-input-label for="contact_phone" :value="__('Phone')" />
        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('contact_phone', $site?->contact_phone)" />
        <x-input-error class="mt-2" :messages="$errors->get('contact_phone')" />
    </div>
    <div>
        <x-input-label for="contact_email" :value="__('Email')" />
        <x-text-input id="contact_email" name="contact_email" type="email" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('contact_email', $site?->contact_email)" />
        <x-input-error class="mt-2" :messages="$errors->get('contact_email')" />
    </div>
</div>

<div class="mt-4 flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-slate-300 text-bucha-primary" @checked(old('is_active', $site?->is_active ?? true))>
    <x-input-label for="is_active" :value="__('Active site')" class="mb-0" />
</div>

<script>
    (function () {
        const type = document.getElementById('site_type');
        const eventFields = document.getElementById('event-fields');
        const contactHint = document.getElementById('contact-hint');
        const contactRequired = @json([\App\Support\SalesComplianceCatalog::SITE_RESTAURANT, \App\Support\SalesComplianceCatalog::SITE_BAR, \App\Support\SalesComplianceCatalog::SITE_BUTCHERY]);
        function sync() {
            const isEvent = type.value === @json(\App\Support\SalesComplianceCatalog::SITE_PRIVATE_EVENT);
            eventFields.classList.toggle('hidden', !isEvent);
            contactHint.textContent = contactRequired.includes(type.value)
                ? @json(__('Required for restaurants, bars, and butcheries.'))
                : @json(__('Optional for private / individual events.'));
        }
        type.addEventListener('change', sync);
        sync();
    })();

    window.salesComplianceLocationDropdowns = function () {
        const baseUrl = @json(route('divisions.index'));
        return {
            countries: [],
            provinces: [],
            districts: [],
            sectors: [],
            countryId: @json($locationCountryId),
            provinceId: @json($locationProvinceId),
            districtId: @json($locationDistrictId),
            sectorId: @json($locationSectorId),
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
            async loadCountries() {
                this.countries = await this.fetchChildren(null);
                if (! this.countryId) {
                    const rwanda = this.countries.find((country) => String(country.name || '').toLowerCase() === 'rwanda');
                    if (rwanda) {
                        this.countryId = String(rwanda.id);
                    }
                }
                await this.restoreCascade();
            },
            async restoreCascade() {
                if (! this.countryId) {
                    return;
                }
                this.provinces = await this.fetchChildren(this.countryId);
                if (this.provinceId) {
                    this.districts = await this.fetchChildren(this.provinceId);
                    if (this.districtId) {
                        this.sectors = await this.fetchChildren(this.districtId);
                    }
                }
            },
            async onCountryChange() {
                this.provinceId = this.districtId = this.sectorId = '';
                this.provinces = this.districts = this.sectors = [];
                if (this.countryId) {
                    this.provinces = await this.fetchChildren(this.countryId);
                }
            },
            async onProvinceChange() {
                this.districtId = this.sectorId = '';
                this.districts = this.sectors = [];
                if (this.provinceId) {
                    this.districts = await this.fetchChildren(this.provinceId);
                }
            },
            async onDistrictChange() {
                this.sectorId = '';
                this.sectors = [];
                if (this.districtId) {
                    this.sectors = await this.fetchChildren(this.districtId);
                }
            },
        };
    };
</script>
