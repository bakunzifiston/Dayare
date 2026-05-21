@php
    $business = $business ?? null;
    $formId = $formId ?? 'business-create-form';
    $formAction = $formAction ?? route('businesses.store');
    $formMethod = $formMethod ?? 'post';
    $draftKey = $draftKey ?? 'bucha-processor-business-onboarding-draft';
    $sidebarBadge = $sidebarBadge ?? __('New business setup');
    $sidebarTitle = $sidebarTitle ?? __('Business Onboarding Wizard');
    $sidebarDescription = $sidebarDescription ?? __('Capture operator survey details covering identity, workforce, operations, and digital readiness.');
    $headerTitle = $headerTitle ?? __('Complete the setup');
    $submitLabel = $submitLabel ?? __('Register business');
    $backUrl = $backUrl ?? route('businesses.hub');
    $backLabel = $backLabel ?? __('← Back to businesses');
    $cancelUrl = $cancelUrl ?? route('businesses.hub');

    $wizardSteps = [
        1 => ['short' => __('Business info'), 'title' => __('Business info'), 'subtitle' => __('Official business details and contact.')],
        2 => ['short' => __('Ownership'), 'title' => __('Ownership info'), 'subtitle' => __('Owner or legal representative details.')],
        3 => ['short' => __('Details'), 'title' => __('Business details'), 'subtitle' => __('Additional profile information for processor onboarding.')],
        4 => ['short' => __('VIBE'), 'title' => __('VIBE metadata'), 'subtitle' => __('Tracking fields used by the VIBE pathway.')],
        5 => ['short' => __('Location'), 'title' => __('Location'), 'subtitle' => __('Business address and administrative location.')],
    ];

    $wizardInitialMembers = old('members');
    if ($wizardInitialMembers === null && $business) {
        $wizardInitialMembers = $business->ownershipMembers->map(fn ($m) => [
            'first_name' => $m->first_name,
            'last_name' => $m->last_name,
            'phone' => $m->phone ?? '',
            'email' => $m->email ?? '',
            'date_of_birth' => $m->date_of_birth?->format('Y-m-d') ?? '',
            'gender' => $m->gender ?? '',
            'pwd_status' => $m->pwd_status ?? '',
        ])->values()->all();
        if (empty($wizardInitialMembers)) {
            $wizardInitialMembers = [['first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '', 'date_of_birth' => '', 'gender' => '', 'pwd_status' => '']];
        }
    } else {
        $wizardInitialMembers = array_values($wizardInitialMembers ?? [['first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '', 'date_of_birth' => '', 'gender' => '', 'pwd_status' => '']]);
    }

    $wizardOwnershipType = old('ownership_type', $business?->ownership_type ?? '');
    $wizardCountryId = old('country_id', $business?->country_id ?? '');
    $wizardProvinceId = old('province_id', $business?->province_id ?? '');
    $wizardDistrictId = old('district_id', $business?->district_id ?? '');
    $wizardSectorId = old('sector_id', $business?->sector_id ?? '');
    $wizardCellId = old('cell_id', $business?->cell_id ?? '');
    $wizardVillageId = old('village_id', $business?->village_id ?? '');

    $wizardErrorStep = (int) old('wizard_step', 0);
    if ($wizardErrorStep < 1 && $errors->any()) {
        $stepFieldMap = [
            1 => ['business_name', 'registration_number', 'tax_id', 'contact_phone', 'email', 'status'],
            2 => ['owner_first_name', 'owner_last_name', 'owner_dob', 'owner_gender', 'owner_pwd_status', 'owner_phone', 'owner_email', 'ownership_type', 'members'],
            3 => ['business_size', 'baseline_revenue'],
            4 => ['vibe_unique_id', 'vibe_commencement_date', 'pathway_status', 'vibe_comments'],
            5 => ['country_id', 'province_id', 'district_id', 'sector_id', 'cell_id', 'village_id', 'city', 'state_region', 'postal_code', 'country'],
        ];
        foreach ($stepFieldMap as $num => $fields) {
            foreach ($fields as $field) {
                if ($errors->has($field) || collect($errors->keys())->contains(fn ($k) => str_starts_with($k, $field.'.'))) {
                    $wizardErrorStep = $num;
                    break 2;
                }
            }
        }
        if ($wizardErrorStep < 1) {
            $wizardErrorStep = 1;
        }
    }
@endphp

<div
    class="-mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6"
    x-data="businessOnboardingWizard({
        totalSteps: {{ count($wizardSteps) }},
        initialStep: {{ min(count($wizardSteps), max(1, $wizardErrorStep > 0 ? $wizardErrorStep : (int) old('wizard_step', 1))) }},
        formId: @js($formId),
        members: @js($wizardInitialMembers),
        ownershipType: @js($wizardOwnershipType),
        countryId: @js($wizardCountryId),
        provinceId: @js($wizardProvinceId),
        districtId: @js($wizardDistrictId),
        sectorId: @js($wizardSectorId),
        cellId: @js($wizardCellId),
        villageId: @js($wizardVillageId),
        divisionsUrl: @js(route('divisions.index')),
        draftKey: @js($draftKey),
    })"
    x-init="init()"
>
    <form
        id="{{ $formId }}"
        method="post"
        action="{{ $formAction }}"
        class="min-h-[calc(100vh-3.5rem)]"
        novalidate
        @submit.prevent="submitForm"
    >
        @csrf
        @if ($formMethod === 'patch')
            @method('patch')
        @endif
        <input type="hidden" name="wizard_step" :value="step">

        <div class="flex flex-col lg:flex-row min-h-[calc(100vh-3.5rem)]">
            {{-- Left sidebar --}}
            <aside class="bucha-wizard-sidebar lg:w-[340px] xl:w-[380px] shrink-0 p-6 sm:p-8 lg:p-10 flex flex-col shadow-bucha-md">
                <div class="inline-flex items-center gap-2 rounded-bucha bg-white/15 border border-white/20 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white w-fit">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ $sidebarBadge }}
                </div>
                <h1 class="mt-6 text-2xl sm:text-3xl font-bold leading-tight">{{ $sidebarTitle }}</h1>
                <p class="mt-3 text-sm text-white/75 leading-relaxed">
                    {{ $sidebarDescription }}
                </p>
                <div class="mt-8 space-y-4 flex-1">
                    <div class="rounded-bucha bg-black/15 border border-white/15 p-4 backdrop-blur-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-white/70">{{ __('What to prepare') }}</p>
                        <p class="mt-2 text-sm text-white/90">{{ __('Business identity, owner details, and operations baseline.') }}</p>
                    </div>
                    <div class="rounded-bucha bg-black/15 border border-white/15 p-4 backdrop-blur-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-white/70">{{ __('Data quality') }}</p>
                        <p class="mt-2 text-sm text-white/90">{{ __('All questions are optional. Complete what you have now and update later.') }}</p>
                    </div>
                    <div class="rounded-bucha bg-black/15 border border-white/15 p-4 backdrop-blur-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-white/70">{{ __('Completion rule') }}</p>
                        <p class="mt-2 text-sm text-white/90">{{ __('Location is optional but recommended for full traceability.') }}</p>
                    </div>
                </div>
                <a href="{{ $backUrl }}" class="mt-6 text-sm text-white/80 hover:text-white underline decoration-white/40 hover:decoration-white">{{ $backLabel }}</a>
            </aside>

            {{-- Main panel --}}
            <div class="flex-1 bg-white flex flex-col min-w-0">
                <div class="border-b border-slate-200 px-4 sm:px-8 py-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Wizard') }}</p>
                            <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ $headerTitle }}</h2>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm shrink-0">
                            <p class="font-semibold text-slate-800" x-text="'{{ __('Step') }} ' + step + ' {{ __('of') }} ' + totalSteps"></p>
                            <p class="text-slate-500 mt-0.5" x-text="percentComplete + '% {{ __('completed') }}'"></p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-slate-600">
                        <p x-text="'{{ __('Progress') }}: ' + filledTrackable + '/' + totalTrackable + ' {{ __('answers provided') }}'"></p>
                        <p class="text-slate-500">
                            {{ __('Draft is auto-saved in this browser.') }}
                            <button type="button" @click="clearDraft()" class="text-bucha-primary hover:text-bucha-burgundy font-medium ml-1">{{ __('Clear saved draft') }}</button>
                        </p>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($wizardSteps as $num => $meta)
                            <button
                                type="button"
                                @click="goToStep({{ $num }})"
                                class="rounded-bucha px-3 py-2 text-xs font-semibold transition border shadow-sm"
                                :class="step === {{ $num }}
                                    ? 'bg-bucha-primary text-white border-bucha-primary'
                                    : (step > {{ $num }} ? 'bg-bucha-primary/10 text-bucha-primary border-bucha-primary/30' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300')"
                            >
                                {{ $num }}. {{ $meta['short'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="flex-1 px-4 sm:px-8 py-6 overflow-y-auto">
                    @if ($errors->any())
                        <div class="mb-6 rounded-bucha border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
                            <p class="font-semibold">{{ __('Please fix the following:') }}</p>
                            <ul class="mt-2 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @foreach ($wizardSteps as $num => $meta)
                        <div
                            x-show="step === {{ $num }}"
                            @if($num > 1) x-cloak style="display: none;" @endif
                            class="max-w-3xl w-full"
                        >
                            <div class="flex items-start gap-4 mb-8">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-bucha bg-bucha-primary/10 text-bucha-primary shadow-sm">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <div class="min-w-0 pt-0.5">
                                    <h3 class="text-xl font-bold tracking-tight text-slate-900">{{ $meta['title'] }}</h3>
                                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">{{ $meta['subtitle'] }}</p>
                                </div>
                            </div>

                            <div class="bucha-wizard-panel">
                                @include('businesses.partials.wizard.step-' . match($num) {
                                    1 => 'business-info',
                                    2 => 'ownership',
                                    3 => 'business-details',
                                    4 => 'programme',
                                    5 => 'location',
                                    default => 'business-info',
                                })
                            </div>

                        </div>
                    @endforeach
                </div>

                <div class="border-t border-slate-200 px-4 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-3 bg-slate-50/80">
                    <button
                        type="button"
                        @click="prevStep()"
                        class="inline-flex items-center min-h-[2.75rem] px-4 py-2 rounded-bucha border border-slate-300 bg-white text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-40"
                        :disabled="step <= 1"
                    >
                        {{ __('Prev section') }}
                    </button>
                    <div class="flex gap-3">
                        <a href="{{ $cancelUrl }}" class="inline-flex items-center min-h-[2.75rem] px-4 py-2 rounded-bucha border border-slate-300 bg-white text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                            {{ __('Cancel') }}
                        </a>
                        <button
                            type="button"
                            x-show="step < totalSteps"
                            @click="nextStep()"
                            class="inline-flex items-center min-h-[2.75rem] px-5 py-2 rounded-bucha bg-bucha-primary text-sm font-semibold text-white shadow-sm hover:bg-bucha-burgundy focus:outline-none focus:ring-2 focus:ring-bucha-primary/40"
                        >
                            {{ __('Next section') }}
                        </button>
                        <button
                            type="submit"
                            x-show="step === totalSteps"
                            class="inline-flex items-center min-h-[2.75rem] px-5 py-2 rounded-bucha bg-bucha-primary text-sm font-semibold text-white shadow-sm hover:bg-bucha-burgundy focus:outline-none focus:ring-2 focus:ring-bucha-primary/40"
                        >
                            {{ $submitLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="country_id" :value="countryId || ''">
        <input type="hidden" name="province_id" :value="provinceId || ''">
        <input type="hidden" name="district_id" :value="districtId || ''">
        <input type="hidden" name="sector_id" :value="sectorId || ''">
        <input type="hidden" name="cell_id" :value="cellId || ''">
        <input type="hidden" name="village_id" :value="villageId || ''">
    </form>
</div>

@push('scripts')
<script>
function businessOnboardingWizard(config) {
    return {
        step: config.initialStep > 0 ? config.initialStep : 1,
        totalSteps: config.totalSteps,
        totalTrackable: 0,
        filledTrackable: 0,
        percentComplete: 0,
        ownershipType: config.ownershipType || '',
        maxDate: '{{ date('Y-m-d') }}',
        members: config.members || [],
        countries: [], provinces: [], districts: [], sectors: [], cells: [], villages: [],
        countryId: String(config.countryId || ''),
        provinceId: String(config.provinceId || ''),
        districtId: String(config.districtId || ''),
        sectorId: String(config.sectorId || ''),
        cellId: String(config.cellId || ''),
        villageId: String(config.villageId || ''),
        divisionsUrl: config.divisionsUrl,
        draftKey: config.draftKey,
        formId: config.formId || 'business-create-form',

        init() {
            this.loadCountries();
            this.$watch('step', () => this.updateProgress());
            this.$watch('countryId', () => this.updateProgress());
            this.$watch('provinceId', () => this.updateProgress());
            this.$watch('districtId', () => this.updateProgress());
            this.$watch('sectorId', () => this.updateProgress());
            this.$watch('cellId', () => this.updateProgress());
            this.$watch('villageId', () => this.updateProgress());
            const form = document.getElementById(this.formId);
            if (form) {
                this.totalTrackable = form.querySelectorAll('[data-wizard-track]').length;
                form.querySelectorAll('input, select, textarea').forEach((el) => {
                    el.addEventListener('input', () => {
                        this.updateProgress();
                        this.saveDraft();
                    });
                    el.addEventListener('change', () => {
                        this.updateProgress();
                        this.saveDraft();
                    });
                });
            }
            this.restoreDraft();
            this.updateProgress();
        },

        updateProgress() {
            const form = document.getElementById(this.formId);
            if (!form) return;
            let filled = 0;
            form.querySelectorAll('[data-wizard-track]').forEach((el) => {
                if (String(el.value || '').trim() !== '') filled++;
            });
            this.filledTrackable = filled;
            if (this.totalTrackable === 0) {
                this.percentComplete = 0;
                return;
            }
            this.percentComplete = Math.min(100, Math.round((filled / this.totalTrackable) * 100));
        },

        goToStep(n) {
            if (n >= 1 && n <= this.totalSteps) {
                this.step = n;
                this.saveDraft();
            }
        },

        nextStep() {
            if (this.step < this.totalSteps) {
                this.step++;
                this.saveDraft();
            }
        },

        prevStep() {
            if (this.step > 1) {
                this.step--;
                this.saveDraft();
            }
        },

        addMember() {
            if (['partnership', 'cooperative', 'company'].includes(this.ownershipType)) {
                this.members.push({ first_name: '', last_name: '', phone: '', email: '', date_of_birth: '', gender: '', pwd_status: '' });
            }
        },

        removeMember(i) {
            if (this.members.length > 1) this.members.splice(i, 1);
        },

        memberSectionTitle() {
            if (this.ownershipType === 'partnership') return @js(__('Partnership members'));
            if (this.ownershipType === 'cooperative') return @js(__('Cooperative members'));
            return @js(__('Company members'));
        },

        async fetchChildren(parentId) {
            try {
                const url = parentId ? `${this.divisionsUrl}?parent_id=${parentId}` : this.divisionsUrl;
                const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                return Array.isArray(data) ? data : [];
            } catch (e) {
                return [];
            }
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
            this.updateProgress();
        },

        async onProvinceChange() {
            this.districtId = this.sectorId = this.cellId = this.villageId = '';
            this.districts = this.sectors = this.cells = this.villages = [];
            if (this.provinceId) this.districts = await this.fetchChildren(this.provinceId);
            this.updateProgress();
        },

        async onDistrictChange() {
            this.sectorId = this.cellId = this.villageId = '';
            this.sectors = this.cells = this.villages = [];
            if (this.districtId) this.sectors = await this.fetchChildren(this.districtId);
            this.updateProgress();
        },

        async onSectorChange() {
            this.cellId = this.villageId = '';
            this.cells = this.villages = [];
            if (this.sectorId) this.cells = await this.fetchChildren(this.sectorId);
            this.updateProgress();
        },

        async onCellChange() {
            this.villageId = '';
            this.villages = [];
            if (this.cellId) this.villages = await this.fetchChildren(this.cellId);
            this.updateProgress();
        },

        saveDraft() {
            try {
                const form = document.getElementById(this.formId);
                if (!form) return;
                const data = new FormData(form);
                const payload = { step: this.step, fields: {} };
                data.forEach((value, key) => {
                    if (key === '_token' || key === 'wizard_step') return;
                    if (key.endsWith('[]')) {
                        const k = key.slice(0, -2);
                        if (!payload.fields[k]) payload.fields[k] = [];
                        payload.fields[k].push(value);
                    } else {
                        payload.fields[key] = value;
                    }
                });
                payload.ownershipType = this.ownershipType;
                payload.members = this.members;
                payload.countryId = this.countryId;
                payload.provinceId = this.provinceId;
                payload.districtId = this.districtId;
                payload.sectorId = this.sectorId;
                payload.cellId = this.cellId;
                payload.villageId = this.villageId;
                localStorage.setItem(this.draftKey, JSON.stringify(payload));
            } catch (e) {}
        },

        restoreDraft() {
            try {
                const raw = localStorage.getItem(this.draftKey);
                if (!raw) return;
                const payload = JSON.parse(raw);
                if (payload.step) {
                    const draftStep = parseInt(payload.step, 10);
                    if (draftStep >= 1 && draftStep <= this.totalSteps) {
                        this.step = draftStep;
                    }
                }
                if (payload.ownershipType) this.ownershipType = payload.ownershipType;
                if (payload.members) this.members = payload.members;
                ['countryId','provinceId','districtId','sectorId','cellId','villageId'].forEach((k) => {
                    if (payload[k]) this[k] = String(payload[k]);
                });
                const form = document.getElementById(this.formId);
                if (!form || !payload.fields) return;
                Object.entries(payload.fields).forEach(([key, value]) => {
                    if (Array.isArray(value)) {
                        form.querySelectorAll(`[name="${key}[]"]`).forEach((el, i) => {
                            if (value[i] !== undefined) {
                                if (el.type === 'checkbox') el.checked = true;
                                else el.value = value[i];
                            }
                        });
                    } else {
                        const el = form.querySelector(`[name="${key}"]`);
                        if (el) {
                            if (el.type === 'checkbox') el.checked = value === '1' || value === true;
                            else el.value = value;
                        }
                    }
                });
            } catch (e) {}
        },

        clearDraft() {
            localStorage.removeItem(this.draftKey);
        },

        submitForm() {
            this.clearDraft();
            document.getElementById(this.formId).submit();
        },
    };
}
</script>
@endpush
