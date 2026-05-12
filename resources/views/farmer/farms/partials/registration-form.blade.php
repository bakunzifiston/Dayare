@php
    $business = $business ?? null;
    $farm = $farm ?? null;
    $showBusinessSelect = (bool) ($showBusinessSelect ?? false);
    $farmerBusinesses = $farmerBusinesses ?? collect();
    $selectedBusiness = $selectedBusiness ?? null;
    $ownershipType = old('ownership_type', in_array($business?->ownership_type, ['sole_proprietor', 'cooperative', 'company'], true) ? $business->ownership_type : 'sole_proprietor');
    $organizationName = old('organization_name', in_array($ownershipType, ['cooperative', 'company'], true) ? ($business?->business_name ?? '') : '');
    $defaultMember = ['first_name' => '', 'last_name' => '', 'date_of_birth' => '', 'phone' => '', 'gender' => ''];
    $existingMembers = $business?->ownershipMembers
        ?->sortBy('sort_order')
        ->map(fn ($member) => [
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'date_of_birth' => $member->date_of_birth?->format('Y-m-d'),
            'phone' => $member->phone,
            'gender' => $member->gender,
        ])
        ->values()
        ->all() ?? [];
    $initialMembers = old('members', $existingMembers !== [] ? $existingMembers : [$defaultMember]);
@endphp

<div class="space-y-8" x-data="farmRegistrationForm(@js($farmerBusinesses->map(fn ($item) => [
    'id' => $item->id,
    'business_name' => $item->business_name,
    'owner_first_name' => $item->owner_first_name,
    'owner_last_name' => $item->owner_last_name,
    'owner_national_id' => $item->owner_national_id,
    'contact_phone' => $item->contact_phone,
    'email' => $item->email,
    'owner_emergency_contact' => $item->owner_emergency_contact,
    'ownership_type' => $item->ownership_type,
    'tax_id' => $item->tax_id,
    'owner_dob' => $item->owner_dob?->format('Y-m-d'),
    'owner_gender' => $item->owner_gender,
    'members' => $item->ownershipMembers
        ?->sortBy('sort_order')
        ->map(fn ($member) => [
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'date_of_birth' => $member->date_of_birth?->format('Y-m-d'),
            'phone' => $member->phone,
            'gender' => $member->gender,
        ])
        ->values()
        ->all() ?? [],
])->values()), @js($initialMembers))">
    <section class="rounded-bucha border border-slate-200/60 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-4">
            <h3 class="text-base font-semibold text-slate-800">{{ __('Farm owner information') }}</h3>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('Primary owner or legal representative details for this farm registration.') }}</p>
        </div>
        <div class="space-y-4 p-6">
            @if ($showBusinessSelect)
                <div>
                    <x-input-label for="business_id" :value="__('Farmer business')" />
                    <select
                        name="business_id"
                        id="business_id"
                        required
                        x-model="businessId"
                        @change="applyBusinessProfile()"
                        class="mt-1 block w-full rounded-lg border-gray-300"
                    >
                        @foreach ($farmerBusinesses as $b)
                            <option value="{{ $b->id }}" @selected((int) old('business_id', $selectedBusiness?->id) === $b->id)>{{ $b->business_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('business_id')" class="mt-2" />
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="owner_first_name" :value="__('First name')" />
                    <x-text-input id="owner_first_name" name="owner_first_name" type="text" class="mt-1 block w-full" x-model="ownerFirstName" :value="old('owner_first_name', $business?->owner_first_name)" required />
                    <x-input-error :messages="$errors->get('owner_first_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="owner_last_name" :value="__('Last name')" />
                    <x-text-input id="owner_last_name" name="owner_last_name" type="text" class="mt-1 block w-full" x-model="ownerLastName" :value="old('owner_last_name', $business?->owner_last_name)" required />
                    <x-input-error :messages="$errors->get('owner_last_name')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="owner_national_id" :value="__('National ID')" />
                    <x-text-input id="owner_national_id" name="owner_national_id" type="text" class="mt-1 block w-full" x-model="ownerNationalId" :value="old('owner_national_id', $business?->owner_national_id)" required />
                    <x-input-error :messages="$errors->get('owner_national_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="contact_phone" :value="__('Phone number')" />
                    <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full" x-model="contactPhone" :value="old('contact_phone', $business?->contact_phone)" required />
                    <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" x-model="email" :value="old('email', $business?->email)" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="owner_emergency_contact" :value="__('Emergency contact')" />
                    <x-text-input id="owner_emergency_contact" name="owner_emergency_contact" type="text" class="mt-1 block w-full" x-model="ownerEmergencyContact" :value="old('owner_emergency_contact', $business?->owner_emergency_contact)" required />
                    <x-input-error :messages="$errors->get('owner_emergency_contact')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="ownership_type" :value="__('Ownership structure')" />
                <select id="ownership_type" name="ownership_type" x-model="ownershipType" @change="handleOwnershipTypeChange()" class="mt-1 block w-full rounded-lg border-gray-300">
                    <option value="sole_proprietor" @selected($ownershipType === 'sole_proprietor')>{{ __('Sole property') }}</option>
                    <option value="cooperative" @selected($ownershipType === 'cooperative')>{{ __('Cooperative') }}</option>
                    <option value="company" @selected($ownershipType === 'company')>{{ __('Company') }}</option>
                </select>
                <x-input-error :messages="$errors->get('ownership_type')" class="mt-2" />
            </div>

            <div x-show="isCommercialOwnership" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="organization_name" :value="__('Cooperative or company name')" />
                    <x-text-input id="organization_name" name="organization_name" type="text" class="mt-1 block w-full" x-model="organizationName" :value="$organizationName" />
                    <x-input-error :messages="$errors->get('organization_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="tax_id" :value="__('TIN')" />
                    <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full" x-model="taxId" :value="old('tax_id', $business?->tax_id)" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Required for cooperative and company registrations.') }}</p>
                    <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
                </div>
            </div>

            <div x-show="isCommercialOwnership" x-cloak class="space-y-4 border-t border-slate-200 pt-4">
                <div>
                    <h4 class="text-sm font-semibold text-slate-700" x-text="ownershipType === 'cooperative' ? '{{ __('Cooperative members') }}' : '{{ __('Company members') }}'"></h4>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Add each member with their personal and contact details.') }}</p>
                </div>

                <template x-for="(member, index) in members" :key="index">
                    <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-600" x-text="'{{ __('Member') }} ' + (index + 1)"></span>
                            <button type="button" @click="removeMember(index)" class="text-sm text-red-600 hover:text-red-700" x-show="members.length > 1">{{ __('Remove') }}</button>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('First name') }}</label>
                                <input type="text" :name="'members[' + index + '][first_name]'" x-model="member.first_name" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Last name') }}</label>
                                <input type="text" :name="'members[' + index + '][last_name]'" x-model="member.last_name" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" required>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Date of birth') }}</label>
                                <input type="date" :name="'members[' + index + '][date_of_birth]'" x-model="member.date_of_birth" :max="maxDate" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Phone number') }}</label>
                                <input type="text" :name="'members[' + index + '][phone]'" x-model="member.phone" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" required>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Gender') }}</label>
                            <select :name="'members[' + index + '][gender]'" x-model="member.gender" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" required>
                                <option value="">{{ __('Select gender') }}</option>
                                @foreach (\App\Models\Business::OWNER_GENDERS as $gender)
                                    <option value="{{ $gender }}">{{ __(ucfirst($gender)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </template>

                <button type="button" @click="addMember()" class="inline-flex items-center rounded-lg border border-dashed border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    {{ __('Add member') }}
                </button>

                <x-input-error :messages="$errors->get('members')" class="mt-2" />
                <x-input-error :messages="$errors->get('members.*.first_name')" class="mt-2" />
                <x-input-error :messages="$errors->get('members.*.last_name')" class="mt-2" />
                <x-input-error :messages="$errors->get('members.*.date_of_birth')" class="mt-2" />
                <x-input-error :messages="$errors->get('members.*.phone')" class="mt-2" />
                <x-input-error :messages="$errors->get('members.*.gender')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="owner_dob" :value="__('Date of birth')" />
                    <x-text-input id="owner_dob" name="owner_dob" type="date" class="mt-1 block w-full" x-model="ownerDob" :value="old('owner_dob', $business?->owner_dob?->format('Y-m-d'))" max="{{ date('Y-m-d') }}" required />
                    <x-input-error :messages="$errors->get('owner_dob')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="owner_gender" :value="__('Gender')" />
                    <select id="owner_gender" name="owner_gender" x-model="ownerGender" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        <option value="">{{ __('Select gender') }}</option>
                        @foreach (\App\Models\Business::OWNER_GENDERS as $gender)
                            <option value="{{ $gender }}" @selected(old('owner_gender', $business?->owner_gender) === $gender)>{{ __(ucfirst($gender)) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('owner_gender')" class="mt-2" />
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200/60 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-4">
            <h3 class="text-base font-semibold text-slate-800">{{ __('Farm information') }}</h3>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('Registered farm location, size, and operational status.') }}</p>
        </div>
        <div class="space-y-4 p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" :value="__('Farm name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $farm?->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="registration_number" :value="__('Farm code / registration number')" />
                    <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full" :value="old('registration_number', $farm?->registration_number)" required />
                    <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="gps_latitude" :value="__('GPS latitude')" />
                    <x-text-input id="gps_latitude" name="gps_latitude" type="text" inputmode="decimal" class="mt-1 block w-full" :value="old('gps_latitude', $farm?->gps_latitude)" />
                    <x-input-error :messages="$errors->get('gps_latitude')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="gps_longitude" :value="__('GPS longitude')" />
                    <x-text-input id="gps_longitude" name="gps_longitude" type="text" inputmode="decimal" class="mt-1 block w-full" :value="old('gps_longitude', $farm?->gps_longitude)" />
                    <x-input-error :messages="$errors->get('gps_longitude')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="farm_size_hectares" :value="__('Farm size (hectares)')" />
                    <x-text-input id="farm_size_hectares" name="farm_size_hectares" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('farm_size_hectares', $farm?->farm_size_hectares)" required />
                    <x-input-error :messages="$errors->get('farm_size_hectares')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="land_ownership_type" :value="__('Ownership type')" />
                    <select id="land_ownership_type" name="land_ownership_type" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        <option value="">{{ __('Select ownership type') }}</option>
                        @foreach (\App\Models\Farm::LAND_OWNERSHIP_TYPES as $type)
                            <option value="{{ $type }}" @selected(old('land_ownership_type', $farm?->land_ownership_type) === $type)>{{ __(ucfirst(str_replace('_', ' ', $type))) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('land_ownership_type')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="registration_date" :value="__('Registration date')" />
                    <x-text-input id="registration_date" name="registration_date" type="date" class="mt-1 block w-full" :value="old('registration_date', $farm?->registration_date?->format('Y-m-d') ?? now()->toDateString())" max="{{ date('Y-m-d') }}" required />
                    <x-input-error :messages="$errors->get('registration_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Farm status')" />
                    <select name="status" id="status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        @foreach (\App\Models\Farm::STATUSES as $s)
                            <option value="{{ $s }}" @selected(old('status', $farm?->status ?? \App\Models\Farm::STATUS_ACTIVE) === $s)>{{ __(ucfirst($s)) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
            </div>

            <div>
                <span class="mb-2 block text-sm font-medium text-slate-700">{{ __('Animal types produced (optional)') }}</span>
                <div class="flex flex-wrap gap-3">
                    @foreach (\App\Support\FarmerAnimalType::ALL as $t)
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="animal_types[]" value="{{ $t }}" @checked(collect(old('animal_types', $farm?->animal_types ?? []))->contains($t)) />
                            {{ \App\Support\FarmerAnimalType::label($t) }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    function farmRegistrationForm(businesses, initialMembers) {
        return {
            businesses,
            businessId: String(@js(old('business_id', $selectedBusiness?->id ?? ''))),
            ownershipType: @js($ownershipType),
            ownerFirstName: @js(old('owner_first_name', $business?->owner_first_name ?? '')),
            ownerLastName: @js(old('owner_last_name', $business?->owner_last_name ?? '')),
            ownerNationalId: @js(old('owner_national_id', $business?->owner_national_id ?? '')),
            contactPhone: @js(old('contact_phone', $business?->contact_phone ?? '')),
            email: @js(old('email', $business?->email ?? '')),
            ownerEmergencyContact: @js(old('owner_emergency_contact', $business?->owner_emergency_contact ?? '')),
            organizationName: @js($organizationName),
            taxId: @js(old('tax_id', $business?->tax_id ?? '')),
            ownerDob: @js(old('owner_dob', $business?->owner_dob?->format('Y-m-d') ?? '')),
            ownerGender: @js(old('owner_gender', $business?->owner_gender ?? '')),
            members: initialMembers,
            maxDate: new Date().toISOString().split('T')[0],
            get isCommercialOwnership() {
                return this.ownershipType === 'cooperative' || this.ownershipType === 'company';
            },
            handleOwnershipTypeChange() {
                if (this.isCommercialOwnership && this.members.length === 0) {
                    this.addMember();
                }

                if (! this.isCommercialOwnership) {
                    this.members = [];
                }
            },
            addMember() {
                this.members.push({
                    first_name: '',
                    last_name: '',
                    date_of_birth: '',
                    phone: '',
                    gender: '',
                });
            },
            removeMember(index) {
                if (this.members.length > 1) {
                    this.members.splice(index, 1);
                }
            },
            applyBusinessProfile() {
                const business = this.businesses.find((item) => String(item.id) === String(this.businessId));
                if (! business) {
                    return;
                }

                this.ownerFirstName = business.owner_first_name || '';
                this.ownerLastName = business.owner_last_name || '';
                this.ownerNationalId = business.owner_national_id || '';
                this.contactPhone = business.contact_phone || '';
                this.email = business.email || '';
                this.ownerEmergencyContact = business.owner_emergency_contact || '';
                this.ownershipType = ['cooperative', 'company', 'sole_proprietor'].includes(business.ownership_type)
                    ? business.ownership_type
                    : 'sole_proprietor';
                this.taxId = business.tax_id || '';
                this.ownerDob = business.owner_dob || '';
                this.ownerGender = business.owner_gender || '';
                this.organizationName = ['cooperative', 'company'].includes(this.ownershipType)
                    ? (business.business_name || '')
                    : '';
                this.members = (business.members && business.members.length > 0)
                    ? business.members
                    : (this.isCommercialOwnership ? [{ first_name: '', last_name: '', date_of_birth: '', phone: '', gender: '' }] : []);
            },
        };
    }
</script>
