@php $business = $business ?? null; @endphp
<div class="bucha-wizard-form">
    <x-wizard-section :title="__('Owner details')" :subtitle="__('Legal owner or primary representative for this business.')">
        <div class="bucha-wizard-grid">
            <x-wizard-field for="owner_first_name" :label="__('First name')">
                <input id="owner_first_name" name="owner_first_name" type="text" class="bucha-wizard-input" value="{{ old('owner_first_name', $business?->owner_first_name ?? $business?->owner_name) }}" data-wizard-track />
                <x-input-error class="mt-2" :messages="$errors->get('owner_first_name')" />
            </x-wizard-field>
            <x-wizard-field for="owner_last_name" :label="__('Last name')">
                <input id="owner_last_name" name="owner_last_name" type="text" class="bucha-wizard-input" value="{{ old('owner_last_name', $business?->owner_last_name) }}" data-wizard-track />
                <x-input-error class="mt-2" :messages="$errors->get('owner_last_name')" />
            </x-wizard-field>
        </div>

        <x-wizard-field for="owner_dob" :label="__('Date of birth')" :hint="__('Optional — used for demographic reporting.')">
            <input id="owner_dob" name="owner_dob" type="date" class="bucha-wizard-input" data-wizard-track value="{{ old('owner_dob', $business?->owner_dob?->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}" />
            <x-input-error class="mt-2" :messages="$errors->get('owner_dob')" />
        </x-wizard-field>

        <div class="bucha-wizard-grid">
            <x-wizard-field for="owner_gender" :label="__('Owner gender')">
                <select id="owner_gender" name="owner_gender" class="bucha-wizard-select" data-wizard-track>
                    <option value="">{{ __('Select gender') }}</option>
                    @foreach (\App\Models\Business::OWNER_GENDERS as $gender)
                        <option value="{{ $gender }}" @selected(old('owner_gender', $business?->owner_gender) === $gender)>{{ __(ucfirst($gender)) }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('owner_gender')" />
            </x-wizard-field>
            <x-wizard-field for="owner_pwd_status" :label="__('Disability status')">
                <select id="owner_pwd_status" name="owner_pwd_status" class="bucha-wizard-select" data-wizard-track>
                    <option value="">{{ __('Select status') }}</option>
                    @foreach (\App\Models\Business::OWNER_PWD_STATUSES as $pwdStatus)
                        <option value="{{ $pwdStatus }}" @selected(old('owner_pwd_status', $business?->owner_pwd_status) === $pwdStatus)>{{ __(ucfirst($pwdStatus)) }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('owner_pwd_status')" />
            </x-wizard-field>
        </div>

        <div class="bucha-wizard-grid">
            <x-wizard-field for="owner_phone" :label="__('Owner phone')">
                <input id="owner_phone" name="owner_phone" type="tel" class="bucha-wizard-input" data-wizard-track value="{{ old('owner_phone', $business?->owner_phone) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('owner_phone')" />
            </x-wizard-field>
            <x-wizard-field for="owner_email" :label="__('Owner email')">
                <input id="owner_email" name="owner_email" type="email" class="bucha-wizard-input" data-wizard-track value="{{ old('owner_email', $business?->owner_email) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('owner_email')" />
            </x-wizard-field>
        </div>
    </x-wizard-section>

    <x-wizard-section :title="__('Ownership structure')" :subtitle="__('Select the legal form. Additional members may be required for partnerships and cooperatives.')">
        <x-wizard-field for="ownership_type" :label="__('Ownership type')">
            <select id="ownership_type" name="ownership_type" x-model="ownershipType" @change="if ((ownershipType === 'partnership' || ownershipType === 'cooperative' || ownershipType === 'company') && members.length === 0) addMember()" class="bucha-wizard-select" data-wizard-track>
                <option value="">{{ __('Select type') }}</option>
                @foreach (\App\Models\Business::OWNERSHIP_TYPES as $t)
                    <option value="{{ $t }}" @selected(old('ownership_type', $business?->ownership_type) === $t)>{{ __(ucfirst(str_replace('_', ' ', $t))) }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('ownership_type')" />
        </x-wizard-field>

        <div x-show="ownershipType === 'cooperative'" x-cloak class="space-y-4 pt-4 border-t border-slate-200">
            <p class="text-sm font-semibold text-slate-700">{{ __('Cooperative membership statistics') }}</p>
            <div class="bucha-wizard-grid">
                <x-wizard-field for="total_members" :label="__('Total members/shareholders')">
                    <input id="total_members" name="total_members" type="number" min="0" class="bucha-wizard-input" value="{{ old('total_members', $business?->total_members) }}" data-wizard-track />
                    <x-input-error class="mt-2" :messages="$errors->get('total_members')" />
                </x-wizard-field>
                <x-wizard-field for="female_members" :label="__('Female members')">
                    <input id="female_members" name="female_members" type="number" min="0" class="bucha-wizard-input" value="{{ old('female_members', $business?->female_members) }}" data-wizard-track />
                </x-wizard-field>
            </div>
            <div class="bucha-wizard-grid">
                <x-wizard-field for="members_18_35" :label="__('Members aged 18–35')">
                    <input id="members_18_35" name="members_18_35" type="number" min="0" class="bucha-wizard-input" value="{{ old('members_18_35', $business?->members_18_35) }}" data-wizard-track />
                </x-wizard-field>
                <x-wizard-field for="young_women_members" :label="__('Young women members (18–35)')">
                    <input id="young_women_members" name="young_women_members" type="number" min="0" class="bucha-wizard-input" value="{{ old('young_women_members', $business?->young_women_members) }}" data-wizard-track />
                </x-wizard-field>
            </div>
        </div>

        <div x-show="ownershipType === 'partnership' || ownershipType === 'cooperative' || ownershipType === 'company'" x-cloak class="space-y-4">
            <p class="text-sm font-semibold text-slate-700" x-text="memberSectionTitle()"></p>
            <template x-for="(member, index) in members" :key="index">
                <div class="bucha-wizard-member-card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-semibold text-slate-700" x-text="'{{ __('Member') }} ' + (index + 1)"></span>
                        <button type="button" @click="removeMember(index)" class="text-sm font-medium text-red-600 hover:text-red-700" x-show="members.length > 1">{{ __('Remove') }}</button>
                    </div>
                    <div class="bucha-wizard-grid">
                        <x-wizard-field :label="__('First name')">
                            <input type="text" :name="'members[' + index + '][first_name]'" x-model="member.first_name" class="bucha-wizard-input" data-wizard-track />
                        </x-wizard-field>
                        <x-wizard-field :label="__('Last name')">
                            <input type="text" :name="'members[' + index + '][last_name]'" x-model="member.last_name" class="bucha-wizard-input" data-wizard-track />
                        </x-wizard-field>
                    </div>
                    <div class="bucha-wizard-grid">
                        <x-wizard-field :label="__('Phone number')">
                            <input type="tel" :name="'members[' + index + '][phone]'" x-model="member.phone" class="bucha-wizard-input" data-wizard-track />
                        </x-wizard-field>
                        <x-wizard-field :label="__('Email')">
                            <input type="email" :name="'members[' + index + '][email]'" x-model="member.email" class="bucha-wizard-input" data-wizard-track />
                        </x-wizard-field>
                    </div>
                    <x-wizard-field :label="__('Date of birth')">
                        <input type="date" :name="'members[' + index + '][date_of_birth]'" x-model="member.date_of_birth" :max="maxDate" class="bucha-wizard-input" data-wizard-track />
                    </x-wizard-field>
                    <div class="bucha-wizard-grid">
                        <x-wizard-field :label="__('Gender')">
                            <select :name="'members[' + index + '][gender]'" x-model="member.gender" class="bucha-wizard-select" data-wizard-track>
                                <option value="">{{ __('Select gender') }}</option>
                                @foreach (\App\Models\Business::OWNER_GENDERS as $gender)
                                    <option value="{{ $gender }}">{{ __(ucfirst($gender)) }}</option>
                                @endforeach
                            </select>
                        </x-wizard-field>
                        <x-wizard-field :label="__('Disability status')">
                            <select :name="'members[' + index + '][pwd_status]'" x-model="member.pwd_status" class="bucha-wizard-select" data-wizard-track>
                                <option value="">{{ __('Select status') }}</option>
                                @foreach (\App\Models\Business::OWNER_PWD_STATUSES as $pwdStatus)
                                    <option value="{{ $pwdStatus }}">{{ __(ucfirst($pwdStatus)) }}</option>
                                @endforeach
                            </select>
                        </x-wizard-field>
                    </div>
                </div>
            </template>
            <button type="button" @click="addMember()" class="bucha-wizard-add-btn">
                {{ __('Add member') }}
            </button>
        </div>
    </x-wizard-section>
</div>
