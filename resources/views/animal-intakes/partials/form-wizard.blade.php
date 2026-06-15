@php
    use App\Models\AnimalIntake;
    use App\Models\AnimalIntakeItem;

    $intake = $intake ?? null;
    $isEdit = ($mode ?? 'create') === 'edit';
    $speciesOptions = auth()->user()?->configuredSpeciesNames() ?? collect(AnimalIntake::SPECIES_OPTIONS);
    $defaultSpecies = $speciesOptions->first() ?? AnimalIntake::SPECIES_CATTLE;
    $intakeLocalNow = now(config('app.display_timezone', 'Africa/Kigali'))->format('Y-m-d\TH:i');

    $blankAnimal = [
        'id' => null,
        'ear_tag' => '',
        'species' => $defaultSpecies,
        'sex' => AnimalIntake::SEX_MALE,
        'age_months' => '',
        'live_weight_kg' => '',
        'body_condition_score' => 'good',
        'unit_price' => '',
        'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        'notes' => '',
        'slaughter_plan_id' => null,
    ];

    if (old('animals')) {
        $initialAnimals = collect(old('animals'))->map(function ($row) {
            return array_merge([
                'id' => null,
                'ear_tag' => '',
                'species' => $defaultSpecies,
                'sex' => AnimalIntake::SEX_MALE,
                'age_months' => '',
                'live_weight_kg' => '',
                'body_condition_score' => 'good',
                'unit_price' => '',
                'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
                'notes' => '',
                'slaughter_plan_id' => null,
            ], is_array($row) ? $row : []);
        })->values()->all();
    } elseif ($intake?->relationLoaded('items') && $intake->items->isNotEmpty()) {
        $initialAnimals = $intake->items->map(fn ($item) => [
            'id' => $item->id,
            'ear_tag' => $item->ear_tag,
            'species' => $item->species,
            'sex' => $item->sex,
            'age_months' => $item->age_months ?? '',
            'live_weight_kg' => $item->live_weight_kg ?? '',
            'body_condition_score' => $item->body_condition_score ?? 'good',
            'unit_price' => $item->unit_price,
            'health_status' => $item->health_status,
            'notes' => $item->notes ?? '',
            'slaughter_plan_id' => $item->slaughter_plan_id,
        ])->values()->all();
    } else {
        $initialAnimals = [$blankAnimal];
    }

    $defaultSourceType = old(
        'source_type',
        $intake?->source_type ?? AnimalIntake::SOURCE_TYPE_CLIENT,
    );

    $errorStep = 1;
    $hasAnimalErrors = $errors->has('animals');
    if (! $hasAnimalErrors) {
        foreach ($errors->keys() as $errorKey) {
            if (str_starts_with($errorKey, 'animals.')) {
                $hasAnimalErrors = true;
                break;
            }
        }
    }
    if ($hasAnimalErrors) {
        $errorStep = 2;
    } elseif ($errors->hasAny([
        'health_certificate_number', 'health_certificate_issue_date', 'health_certificate_expiry_date',
        'movement_permit_number', 'movement_permit_document',
    ])) {
        $errorStep = 3;
    } elseif ($errors->any()) {
        $errorStep = 1;
    }

    $locationDefaults = [
        'country_id' => old('country_id', $intake?->country_id ?? ''),
        'province_id' => old('province_id', $intake?->province_id ?? ''),
        'district_id' => old('district_id', $intake?->district_id ?? ''),
        'sector_id' => old('sector_id', $intake?->sector_id ?? ''),
        'cell_id' => old('cell_id', $intake?->cell_id ?? ''),
        'village_id' => old('village_id', $intake?->village_id ?? ''),
    ];
@endphp

<style>
    .intake-wizard-step { display: none; }
    .intake-wizard-step.is-active { display: block; }
    .intake-health-badge { display: inline-flex; align-items: center; padding: 0.15rem 0.55rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
    .intake-health-badge--healthy { background: #dcfce7; color: #166534; }
    .intake-health-badge--observation { background: #fef3c7; color: #92400e; }
    .intake-health-badge--rejected { background: #fee2e2; color: #991b1b; }
    .intake-summary-bar { position: sticky; top: 0; z-index: 10; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.75rem 1rem; }
    .intake-animal-card { border: 1px solid #e2e8f0; border-radius: 0.75rem; background: #fff; }
    .intake-animal-card.is-assigned { border-color: #cbd5e1; background: #f8fafc; }
    .intake-step-nav { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
    .intake-step-pill { flex: 1; min-width: 7rem; text-align: center; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; border: 1px solid #e2e8f0; color: #64748b; background: #fff; }
    .intake-step-pill.is-current { border-color: #7f1d1d; color: #7f1d1d; background: #fef2f2; }
    .intake-step-pill.is-done { border-color: #bbf7d0; color: #166534; background: #f0fdf4; }
</style>

<script>
    window.animalIntakeFieldErrors = @json(collect($errors->getMessages())->filter(fn ($_, $key) => str_starts_with($key, 'animals.'))->all());
    window.animalIntakeBlankAnimal = @json($blankAnimal);
    window.animalIntakeLocationDefaults = @json($locationDefaults);

    window.animalIntakeWizard = function animalIntakeWizard(config) {
        return {
            step: config.step || 1,
            animals: config.animals || [],
            sourceType: config.sourceType,
            isEdit: config.isEdit,
            isDraftIntake: config.isDraftIntake,
            nextKey: 1,
            initAnimals() {
                this.animals = (this.animals || []).map((a) => ({
                    ...a,
                    _key: this.nextKey++,
                }));
            },
            prepareFormForSubmit(form) {
                if (!form) {
                    return;
                }

                form.querySelectorAll('[disabled]').forEach((field) => {
                    field.disabled = false;
                });
            },
            addAnimal() {
                this.animals.push({
                    ...window.animalIntakeBlankAnimal,
                    _key: this.nextKey++,
                });
            },
            removeAnimal(index) {
                if (this.animals.length <= 1) return;
                if (this.animals[index]?.slaughter_plan_id) return;
                this.animals.splice(index, 1);
            },
            nextStep() {
                if (this.step < 4) this.step++;
            },
            healthCount(status) {
                return this.animals.filter((a) => a.health_status === status).length;
            },
            speciesSummary() {
                const counts = {};
                this.animals.forEach((a) => {
                    if (!a.species) return;
                    counts[a.species] = (counts[a.species] || 0) + 1;
                });
                const parts = Object.entries(counts).map(([s, n]) => n + ' ' + s);
                return parts.length ? parts.join(', ') : '';
            },
            healthLabel(status) {
                const map = {
                    healthy: @js(__('Healthy')),
                    under_observation: @js(__('Observation')),
                    rejected: @js(__('Rejected')),
                };
                return map[status] || status;
            },
            fieldError(index, field) {
                const key = 'animals.' + index + '.' + field;
                const errors = window.animalIntakeFieldErrors[key];
                return errors && errors[0] ? errors[0] : '';
            },
            reviewField(name) {
                const el = document.getElementById(name);
                return el?.value || '—';
            },
            reviewFacility() {
                const el = document.getElementById('facility_id');
                if (!el || !el.selectedOptions[0]) return '—';
                return el.selectedOptions[0].text;
            },
            reviewSource() {
                if (this.sourceType === @js(AnimalIntake::SOURCE_TYPE_SUPPLIER)) {
                    const el = document.getElementById('supplier_id');
                    return el?.selectedOptions[0]?.text?.trim() || @js(__('Supplier'));
                }
                const el = document.getElementById('client_id');
                const manual = [this.reviewField('manual_client_firstname'), this.reviewField('manual_client_lastname')].filter((v) => v && v !== '—').join(' ');
                return el?.value ? (el.selectedOptions[0]?.text?.trim() || @js(__('Client'))) : (manual || @js(__('Client')));
            },
            reviewCertDates() {
                const issue = this.reviewField('health_certificate_issue_date');
                const expiry = this.reviewField('health_certificate_expiry_date');
                if (issue === '—' && expiry === '—') return '—';
                return issue + ' → ' + expiry;
            },
        };
    }

    window.locationDropdowns = function locationDropdowns() {
        const baseUrl = @js(route('divisions.index'));
        const defaults = window.animalIntakeLocationDefaults || {};
        return {
            countries: [], provinces: [], districts: [], sectors: [], cells: [], villages: [],
            countryId: defaults.country_id ? String(defaults.country_id) : '',
            provinceId: defaults.province_id ? String(defaults.province_id) : '',
            districtId: defaults.district_id ? String(defaults.district_id) : '',
            sectorId: defaults.sector_id ? String(defaults.sector_id) : '',
            cellId: defaults.cell_id ? String(defaults.cell_id) : '',
            villageId: defaults.village_id ? String(defaults.village_id) : '',
            async fetchChildren(parentId) {
                try {
                    const url = parentId ? `${baseUrl}?parent_id=${parentId}` : baseUrl;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    return Array.isArray(data) ? data : [];
                } catch (e) { return []; }
            },
            async loadCountries() {
                this.countries = await this.fetchChildren(null);
                await this.restoreCascade();
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

    window.suppliersForIntake = @json($suppliersForIntake);
    window.clientsForIntake = @json($clientsForIntake);

    document.addEventListener('DOMContentLoaded', function () {
        const expiryInput = document.getElementById('health_certificate_expiry_date');
        const expiryWarning = document.getElementById('health_certificate_expiry_warning');
        function updateExpiryWarning() {
            if (!expiryInput || !expiryWarning) return;
            const value = expiryInput.value;
            if (!value) {
                expiryWarning.textContent = '';
                expiryWarning.classList.add('hidden');
                expiryWarning.classList.remove('text-amber-600', 'text-red-600');
                return;
            }
            const expiry = new Date(value + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const daysUntil = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
            expiryWarning.classList.remove('hidden', 'text-amber-600', 'text-red-600');
            if (daysUntil < 0) {
                expiryWarning.textContent = @js(__('Certificate has expired — informational only, slaughter is not blocked.'));
                expiryWarning.classList.add('text-red-600');
            } else if (daysUntil <= 30) {
                expiryWarning.textContent = @js(__('Certificate expires within 30 days.'));
                expiryWarning.classList.add('text-amber-600');
            } else {
                expiryWarning.textContent = '';
                expiryWarning.classList.add('hidden');
            }
        }
        expiryInput?.addEventListener('change', updateExpiryWarning);
        expiryInput?.addEventListener('input', updateExpiryWarning);
        updateExpiryWarning();

        document.getElementById('supplier_id')?.addEventListener('change', function () {
            const data = window.suppliersForIntake?.[this.value];
            if (!data) return;
            const farmReg = document.getElementById('farm_registration_number');
            if (farmReg && data.registration_number) farmReg.value = data.registration_number;
        });
        document.getElementById('client_id')?.addEventListener('change', function () {
            const data = window.clientsForIntake?.[this.value];
            if (!data) return;
            const fields = {
                manual_client_firstname: data.first_name,
                manual_client_lastname: data.last_name,
                manual_client_contact: data.phone,
            };
            Object.entries(fields).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el && val) el.value = val;
            });
        });
    });
</script>

<div class="max-w-4xl mx-auto sm:px-6 lg:px-8"
    x-data="window.animalIntakeWizard({
        step: {{ $errorStep }},
        animals: @js($initialAnimals),
        sourceType: @js($defaultSourceType),
        isEdit: @js($isEdit),
        isDraftIntake: @js((bool) ($intake?->is_draft ?? false)),
    })"
    x-init="initAnimals()">

    @if ($errors->has('form'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first('form') }}</div>
    @elseif ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    @if ($isEdit && $intake?->reference)
        <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-600">
            <span class="font-medium text-slate-800">{{ $intake->reference }}</span>
            @if ($intake->is_draft)
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">{{ __('Draft') }}</span>
            @endif
        </div>
    @endif

    <nav class="intake-step-nav" aria-label="{{ __('Intake wizard steps') }}">
        <div class="intake-step-pill" :class="{ 'is-current': step === 1, 'is-done': step > 1 }">{{ __('1. Source') }}</div>
        <div class="intake-step-pill" :class="{ 'is-current': step === 2, 'is-done': step > 2 }">{{ __('2. Animals') }}</div>
        <div class="intake-step-pill" :class="{ 'is-current': step === 3, 'is-done': step > 3 }">{{ __('3. Compliance') }}</div>
        <div class="intake-step-pill" :class="{ 'is-current': step === 4 }">{{ __('4. Review') }}</div>
    </nav>

    <form method="post" action="{{ $formAction }}" enctype="multipart/form-data" id="animal-intake-wizard-form" novalidate
        @submit="prepareFormForSubmit($event.target)"
        onsubmit="this.querySelectorAll('[disabled]').forEach(function (el) { el.disabled = false; }); return true;">
        @csrf
        @if (! empty($formMethod) && strtoupper($formMethod) !== 'POST')
            @method($formMethod)
        @endif

        {{-- Step 1: Source details --}}
        <div class="intake-wizard-step space-y-6" :class="{ 'is-active': step === 1 }">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4">
                <h3 class="text-base font-semibold text-slate-800">{{ __('Facility & date') }}</h3>
                <div>
                    <x-input-label for="facility_id" :value="__('Facility (slaughterhouse)')" />
                    <select id="facility_id" name="facility_id" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                        @if (! $isEdit)
                            <option value="">{{ __('Select facility') }}</option>
                        @endif
                        @foreach ($facilities as $f)
                            <option value="{{ $f->id }}" @selected(old('facility_id', $intake?->facility_id) == $f->id)>{{ $f->facility_name }} ({{ $f->facility_type }})</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                </div>
                <div>
                    <x-input-label for="intake_date" :value="__('Intake date & time')" />
                    <x-text-input id="intake_date" name="intake_date" type="datetime-local" class="mt-1 block w-full"
                        :value="old('intake_date', $intake?->intake_date?->timezone(config('app.display_timezone', 'Africa/Kigali'))->format('Y-m-d\TH:i') ?? $intakeLocalNow)" />
                    <x-input-error class="mt-2" :messages="$errors->get('intake_date')" />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4">
                <h3 class="text-base font-semibold text-slate-800">{{ __('Source details') }}</h3>
                <div>
                    <x-input-label for="source_type" :value="__('Source type')" />
                    <select id="source_type" name="source_type" x-model="sourceType" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                        <option value="{{ AnimalIntake::SOURCE_TYPE_CLIENT }}">{{ __('Client') }}</option>
                        <option value="{{ AnimalIntake::SOURCE_TYPE_SUPPLIER }}">{{ __('Supplier') }}</option>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('source_type')" />
                </div>

                <div x-show="sourceType === '{{ AnimalIntake::SOURCE_TYPE_SUPPLIER }}'" class="space-y-4">
                    <div>
                        <x-input-label for="supplier_id" :value="__('Supplier')" />
                        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm"
                            :disabled="sourceType !== '{{ AnimalIntake::SOURCE_TYPE_SUPPLIER }}'">
                            <option value="">{{ __('Select supplier') }}</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id', $intake?->supplier_id) == $s->id)>
                                    {{ trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) ?: ($s->name ?? '') }}{!! $s->phone ? ' · ' . e($s->phone) : '' !!}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('supplier_id')" />
                    </div>
                    @if ($supplierContracts->isNotEmpty())
                    <div>
                        <x-input-label for="contract_id" :value="__('Supplier contract (optional)')" />
                        <select id="contract_id" name="contract_id" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm"
                            :disabled="sourceType !== '{{ AnimalIntake::SOURCE_TYPE_SUPPLIER }}'">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($supplierContracts as $c)
                                <option value="{{ $c->id }}" @selected(old('contract_id', $intake?->contract_id) == $c->id)>
                                    {{ $c->contract_number }} — {{ $c->title }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('contract_id')" />
                    </div>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="farm_name" :value="__('Farm name')" />
                            <x-text-input id="farm_name" name="farm_name" type="text" class="mt-1 block w-full" :value="old('farm_name', $intake?->farm_name)" />
                            <x-input-error class="mt-2" :messages="$errors->get('farm_name')" />
                        </div>
                        <div>
                            <x-input-label for="farm_registration_number" :value="__('Farm registration number')" />
                            <x-text-input id="farm_registration_number" name="farm_registration_number" type="text" class="mt-1 block w-full"
                                :value="old('farm_registration_number', $intake?->farm_registration_number)" />
                            <x-input-error class="mt-2" :messages="$errors->get('farm_registration_number')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="vehicle_plate" :value="__('Vehicle plate')" />
                            <x-text-input id="vehicle_plate" name="vehicle_plate" type="text" class="mt-1 block w-full"
                                :value="old('vehicle_plate', $intake?->transport_vehicle_plate)"
                                x-bind:disabled="sourceType !== '{{ AnimalIntake::SOURCE_TYPE_SUPPLIER }}'" />
                            <x-input-error class="mt-2" :messages="$errors->get('vehicle_plate')" />
                        </div>
                        <div>
                            <x-input-label for="driver_name" :value="__('Driver name')" />
                            <x-text-input id="driver_name" name="driver_name" type="text" class="mt-1 block w-full"
                                :value="old('driver_name', $intake?->driver_name)" />
                            <x-input-error class="mt-2" :messages="$errors->get('driver_name')" />
                        </div>
                    </div>
                </div>

                <div x-show="sourceType === '{{ AnimalIntake::SOURCE_TYPE_CLIENT }}'" class="space-y-4">
                    <div>
                        <x-input-label for="client_id" :value="__('Client')" />
                        <select id="client_id" name="client_id" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm"
                            :disabled="sourceType !== '{{ AnimalIntake::SOURCE_TYPE_CLIENT }}'">
                            <option value="">{{ __('Select client') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected(old('client_id', $intake?->client_id) == $client->id)>
                                    {{ $client->name }}{!! $client->email ? ' · ' . e($client->email) : '' !!}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('client_id')" />
                    </div>
                    <p class="text-sm text-slate-500">{{ __('Or enter client details manually if not in the list:') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="manual_client_firstname" :value="__('Client first name')" />
                            <x-text-input id="manual_client_firstname" name="manual_client_firstname" type="text" class="mt-1 block w-full"
                                :value="old('manual_client_firstname', $intake?->supplier_firstname)"
                                x-bind:disabled="sourceType !== '{{ AnimalIntake::SOURCE_TYPE_CLIENT }}'" />
                            <x-input-error class="mt-2" :messages="$errors->get('manual_client_firstname')" />
                        </div>
                        <div>
                            <x-input-label for="manual_client_lastname" :value="__('Client last name')" />
                            <x-text-input id="manual_client_lastname" name="manual_client_lastname" type="text" class="mt-1 block w-full"
                                :value="old('manual_client_lastname', $intake?->supplier_lastname)"
                                x-bind:disabled="sourceType !== '{{ AnimalIntake::SOURCE_TYPE_CLIENT }}'" />
                            <x-input-error class="mt-2" :messages="$errors->get('manual_client_lastname')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="manual_client_contact" :value="__('Client contact')" />
                        <x-text-input id="manual_client_contact" name="manual_client_contact" type="text" class="mt-1 block w-full"
                            :value="old('manual_client_contact', $intake?->supplier_contact)"
                            x-bind:disabled="sourceType !== '{{ AnimalIntake::SOURCE_TYPE_CLIENT }}'" />
                        <x-input-error class="mt-2" :messages="$errors->get('manual_client_contact')" />
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4" x-data="window.locationDropdowns()" x-init="loadCountries()">
                <input type="hidden" name="country_id" :value="countryId || ''">
                <input type="hidden" name="province_id" :value="provinceId || ''">
                <input type="hidden" name="district_id" :value="districtId || ''">
                <input type="hidden" name="sector_id" :value="sectorId || ''">
                <input type="hidden" name="cell_id" :value="cellId || ''">
                <input type="hidden" name="village_id" :value="villageId || ''">
                <h3 class="text-base font-semibold text-slate-800">{{ __('Origin location (Rwanda)') }}</h3>
                @foreach (['country' => 'countries', 'province' => 'provinces', 'district' => 'districts', 'sector' => 'sectors', 'cell' => 'cells', 'village' => 'villages'] as $level => $list)
                    @php $idKey = $level === 'country' ? 'countryId' : $level . 'Id'; @endphp
                    <div>
                        <x-input-label :for="$level . '_id'" :value="__(ucfirst($level))" />
                        <select :id="'{{ $level }}_id'" x-model="{{ $idKey }}"
                            @if ($level === 'country') @change="onCountryChange()"
                            @elseif ($level === 'province') @change="onProvinceChange()"
                            @elseif ($level === 'district') @change="onDistrictChange()"
                            @elseif ($level === 'sector') @change="onSectorChange()"
                            @elseif ($level === 'cell') @change="onCellChange()" @endif
                            class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm"
                            @if ($level !== 'country') :disabled="!{{ $level === 'province' ? 'countryId' : ($level === 'district' ? 'provinceId' : ($level === 'sector' ? 'districtId' : ($level === 'cell' ? 'sectorId' : 'cellId'))) }}" @endif>
                            <option value="">{{ __('Select') }} {{ __(ucfirst($level)) }}</option>
                            <template x-for="d in {{ $list }}" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Step 2: Individual animals --}}
        <div class="intake-wizard-step space-y-4" :class="{ 'is-active': step === 2 }">
            <div class="intake-summary-bar">
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                    <div>
                        <span class="font-semibold text-slate-800" x-text="animals.length"></span>
                        <span class="text-slate-600">{{ __('animals') }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="intake-health-badge intake-health-badge--healthy" x-text="healthCount('healthy') + ' {{ __('healthy') }}'"></span>
                        <span class="intake-health-badge intake-health-badge--observation" x-text="healthCount('under_observation') + ' {{ __('observation') }}'"></span>
                        <span class="intake-health-badge intake-health-badge--rejected" x-text="healthCount('rejected') + ' {{ __('rejected') }}'"></span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-slate-500" x-show="speciesSummary()" x-text="speciesSummary()"></p>
            </div>

            <x-input-error class="mb-2" :messages="$errors->get('animals')" />

            <template x-for="(animal, index) in animals" :key="animal._key">
                <div class="intake-animal-card p-4 space-y-3" :class="{ 'is-assigned': animal.slaughter_plan_id }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-semibold text-slate-800">{{ __('Animal') }} <span x-text="index + 1"></span></h4>
                            <span class="intake-health-badge"
                                :class="{
                                    'intake-health-badge--healthy': animal.health_status === 'healthy',
                                    'intake-health-badge--observation': animal.health_status === 'under_observation',
                                    'intake-health-badge--rejected': animal.health_status === 'rejected',
                                }"
                                x-text="healthLabel(animal.health_status)"></span>
                            <span x-show="animal.slaughter_plan_id" class="text-xs text-slate-500">{{ __('Assigned to slaughter plan') }}</span>
                        </div>
                        <button type="button" class="text-xs font-medium text-red-600 hover:text-red-800 disabled:opacity-40"
                            @click="removeAnimal(index)" :disabled="animals.length <= 1 || animal.slaughter_plan_id"
                            x-show="!animal.slaughter_plan_id">{{ __('Remove') }}</button>
                    </div>

                    <input type="hidden" :name="'animals[' + index + '][id]'" :value="animal.id || ''">

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Ear tag') }} <span class="text-red-500">*</span></label>
                            <input type="text" :name="'animals[' + index + '][ear_tag]'" x-model="animal.ear_tag" maxlength="100"
                                :readonly="animal.slaughter_plan_id"
                                class="mt-1 block w-full rounded-md border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm text-sm">
                            <p class="mt-1 text-xs text-red-600" x-show="fieldError(index, 'ear_tag')" x-text="fieldError(index, 'ear_tag')"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Species') }} <span class="text-red-500">*</span></label>
                            <select :name="'animals[' + index + '][species]'" x-model="animal.species"
                                class="mt-1 block w-full rounded-md border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm text-sm">
                                @foreach ($speciesOptions as $s)
                                    <option value="{{ $s }}">{{ __($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Sex') }} <span class="text-red-500">*</span></label>
                            <select :name="'animals[' + index + '][sex]'" x-model="animal.sex"
                                class="mt-1 block w-full rounded-md border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm text-sm">
                                <option value="{{ AnimalIntake::SEX_MALE }}">{{ __('Male') }}</option>
                                <option value="{{ AnimalIntake::SEX_FEMALE }}">{{ __('Female') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Age (months)') }}</label>
                            <input type="number" min="1" max="600" :name="'animals[' + index + '][age_months]'" x-model="animal.age_months"
                                class="mt-1 block w-full rounded-md border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Live weight (kg)') }}</label>
                            <input type="number" step="0.1" min="0.1" :name="'animals[' + index + '][live_weight_kg]'" x-model="animal.live_weight_kg"
                                class="mt-1 block w-full rounded-md border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Body condition') }}</label>
                            <select :name="'animals[' + index + '][body_condition_score]'" x-model="animal.body_condition_score"
                                class="mt-1 block w-full rounded-md border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm text-sm">
                                @foreach (AnimalIntakeItem::BODY_CONDITIONS as $bc)
                                    <option value="{{ $bc }}">{{ ucfirst($bc) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Health status') }} <span class="text-red-500">*</span></label>
                            <select :name="'animals[' + index + '][health_status]'" x-model="animal.health_status"
                                class="mt-1 block w-full rounded-md border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm text-sm">
                                <option value="{{ AnimalIntakeItem::HEALTH_HEALTHY }}">{{ __('Healthy') }}</option>
                                <option value="{{ AnimalIntakeItem::HEALTH_OBSERVATION }}">{{ __('Under observation') }}</option>
                                <option value="{{ AnimalIntakeItem::HEALTH_REJECTED }}">{{ __('Rejected') }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="block text-sm font-medium text-slate-700">{{ __('Notes') }}</label>
                            <textarea :name="'animals[' + index + '][notes]'" x-model="animal.notes" rows="2" maxlength="1000"
                                class="mt-1 block w-full rounded-md border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm text-sm"></textarea>
                        </div>
                    </div>
                </div>
            </template>

            <button type="button" @click="addAnimal()"
                class="w-full rounded-lg border-2 border-dashed border-slate-300 py-3 text-sm font-semibold text-slate-600 hover:border-bucha-primary hover:text-bucha-primary">
                + {{ __('Add another animal') }}
            </button>
        </div>

        {{-- Step 3: Compliance --}}
        <div class="intake-wizard-step space-y-6" :class="{ 'is-active': step === 3 }">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-4">
                <h3 class="text-base font-semibold text-slate-800">{{ __('Compliance documents (optional)') }}</h3>
                <p class="text-sm text-slate-500">{{ __('Health certificate and movement permit details are optional. You can add or update them at any time.') }}</p>

                <h4 class="text-sm font-semibold text-slate-700">{{ __('Animal health certificate') }}</h4>
                <div>
                    <x-input-label for="health_certificate_number" :value="__('Health certificate number')" />
                    <x-text-input id="health_certificate_number" name="health_certificate_number" type="text" class="mt-1 block w-full"
                        :value="old('health_certificate_number', $intake?->animal_health_certificate_number)" />
                    <x-input-error class="mt-2" :messages="$errors->get('health_certificate_number')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="health_certificate_issue_date" :value="__('Issue date')" />
                        <x-text-input id="health_certificate_issue_date" name="health_certificate_issue_date" type="date" class="mt-1 block w-full"
                            :value="old('health_certificate_issue_date', $intake?->health_certificate_issue_date?->format('Y-m-d'))" />
                        <x-input-error class="mt-2" :messages="$errors->get('health_certificate_issue_date')" />
                    </div>
                    <div>
                        <x-input-label for="health_certificate_expiry_date" :value="__('Expiry date')" />
                        <x-text-input id="health_certificate_expiry_date" name="health_certificate_expiry_date" type="date" class="mt-1 block w-full"
                            :value="old('health_certificate_expiry_date', $intake?->health_certificate_expiry_date?->format('Y-m-d'))" />
                        <p id="health_certificate_expiry_warning" class="mt-1 text-xs hidden"></p>
                        <x-input-error class="mt-2" :messages="$errors->get('health_certificate_expiry_date')" />
                    </div>
                </div>

                <h4 class="text-sm font-semibold text-slate-700 pt-2">{{ __('Movement permit') }}</h4>
                <div>
                    <x-input-label for="movement_permit_number" :value="__('Movement permit number')" />
                    <x-text-input id="movement_permit_number" name="movement_permit_number" type="text" class="mt-1 block w-full"
                        :value="old('movement_permit_number', $intake?->movement_permit_no)" />
                    <x-input-error class="mt-2" :messages="$errors->get('movement_permit_number')" />
                </div>
                <div>
                    <x-input-label for="movement_permit_document" :value="__('Movement permit document (upload)')" />
                    <input id="movement_permit_document" name="movement_permit_document" type="file" accept=".pdf,image/jpeg,image/png,image/webp"
                        class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-bucha-primary file:text-white hover:file:bg-bucha-burgundy" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('PDF or image, max 10 MB.') }}</p>
                    @if ($intake?->movement_permit_document_path)
                        <p class="mt-2 text-xs text-slate-600">{{ __('Current file on record.') }}</p>
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get('movement_permit_document')" />
                </div>
            </div>
        </div>

        {{-- Step 4: Review --}}
        <div class="intake-wizard-step space-y-6" :class="{ 'is-active': step === 4 }">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 space-y-3">
                <h3 class="text-base font-semibold text-slate-800">{{ __('Review before submitting') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    <div><dt class="text-slate-500">{{ __('Facility') }}</dt><dd class="font-medium text-slate-800" x-text="reviewFacility()"></dd></div>
                    <div><dt class="text-slate-500">{{ __('Intake date & time') }}</dt><dd class="font-medium text-slate-800" x-text="reviewField('intake_date')"></dd></div>
                    <div><dt class="text-slate-500">{{ __('Source') }}</dt><dd class="font-medium text-slate-800" x-text="reviewSource()"></dd></div>
                    <div><dt class="text-slate-500">{{ __('Animals') }}</dt><dd class="font-medium text-slate-800" x-text="animals.length"></dd></div>
                    <div><dt class="text-slate-500">{{ __('Health certificate') }}</dt><dd class="font-medium text-slate-800" x-text="reviewField('health_certificate_number')"></dd></div>
                    <div><dt class="text-slate-500">{{ __('Certificate validity') }}</dt><dd class="font-medium text-slate-800" x-text="reviewCertDates()"></dd></div>
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h4 class="text-sm font-semibold text-slate-800 mb-3">{{ __('Animals summary') }}</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-2 pr-4">#</th>
                                <th class="py-2 pr-4">{{ __('Ear tag') }}</th>
                                <th class="py-2 pr-4">{{ __('Species') }}</th>
                                <th class="py-2 pr-4">{{ __('Health') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(animal, index) in animals" :key="'review-' + animal._key">
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-4" x-text="index + 1"></td>
                                    <td class="py-2 pr-4 font-medium" x-text="animal.ear_tag || '—'"></td>
                                    <td class="py-2 pr-4" x-text="animal.species"></td>
                                    <td class="py-2 pr-4">
                                        <span class="intake-health-badge"
                                            :class="{
                                                'intake-health-badge--healthy': animal.health_status === 'healthy',
                                                'intake-health-badge--observation': animal.health_status === 'under_observation',
                                                'intake-health-badge--rejected': animal.health_status === 'rejected',
                                            }"
                                            x-text="healthLabel(animal.health_status)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Wizard navigation --}}
        <div class="mt-8 flex flex-wrap items-center gap-3">
            <button type="button" x-show="step > 1" @click="step--"
                class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
                {{ __('Back') }}
            </button>
            <button type="button" x-show="step < 4" @click="nextStep()"
                class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy ml-auto">
                {{ __('Next') }}
            </button>
            <button type="submit" name="is_draft" value="1"
                :class="step === 4 ? 'inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 ml-auto' : 'hidden'">
                {{ __('Save as draft') }}
            </button>
            <button type="submit" name="is_draft" value="0" id="animal-intake-submit-btn"
                :class="step === 4 ? 'inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy' : 'hidden'">
                {{ __('Submit intake') }}
            </button>
            <a href="{{ route('animal-intakes.hub') }}"
                class="inline-flex items-center px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-widest hover:text-slate-700"
                :class="{ 'ml-auto': step < 4 }">
                {{ __('Cancel') }}
            </a>
        </div>
    </form>

    @if ($isEdit && $intake?->is_draft)
        <form method="post" action="{{ route('animal-intakes.submit', $intake) }}" class="mt-4"
            onsubmit="return confirm(@js(__('Submit this draft? Finance payables will be created.')))">
            @csrf
            <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-emerald-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-800">
                {{ __('Submit saved draft without editing') }}
            </button>
            <p class="mt-1 text-xs text-slate-500">{{ __('Use this only if the form above is already complete.') }}</p>
        </form>
    @endif
</div>

