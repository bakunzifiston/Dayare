<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('businesses.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Businesses') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Register Business') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form id="business-create-form" method="post" action="{{ route('businesses.store') }}" class="space-y-8" novalidate onsubmit="var f=document.getElementById('business-create-form');if(f){['country_id','province_id','district_id','sector_id','cell_id','village_id'].forEach(function(id){var s=document.getElementById(id),h=f.querySelector('input[name='+id+']');if(s&&h)h.value=s?s.value:'';});}return true;">
                @csrf

                {{-- Section 1: Business info --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80">
                        <h3 class="text-base font-semibold text-slate-800">{{ __('Business info') }}</h3>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Official business details and contact.') }}</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <x-input-label for="business_name" :value="__('Business name')" />
                            <x-text-input id="business_name" name="business_name" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('business_name')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('business_name')" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="registration_number" :value="__('RDB registration number')" />
                                <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('registration_number')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('registration_number')" />
                            </div>
                            <div>
                                <x-input-label for="tax_id" :value="__('Tax ID')" />
                                <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('tax_id')" />
                                <x-input-error class="mt-2" :messages="$errors->get('tax_id')" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="contact_phone" :value="__('Contact phone')" />
                                <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('contact_phone')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('contact_phone')" />
                            </div>
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('email')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                @foreach (\App\Models\Business::STATUSES as $s)
                                    <option value="{{ $s }}" @selected(old('status', 'active') === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>
                    </div>
                </div>

                {{-- Section 2: Ownership info --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60" x-data="ownershipForm()">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80">
                        <h3 class="text-base font-semibold text-slate-800">{{ __('Ownership info') }}</h3>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Owner or legal representative details.') }}</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="owner_first_name" :value="__('First name')" />
                                <x-text-input id="owner_first_name" name="owner_first_name" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('owner_first_name')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('owner_first_name')" />
                            </div>
                            <div>
                                <x-input-label for="owner_last_name" :value="__('Last name')" />
                                <x-text-input id="owner_last_name" name="owner_last_name" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('owner_last_name')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('owner_last_name')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="owner_dob" :value="__('Date of birth')" />
                            <x-text-input id="owner_dob" name="owner_dob" type="date" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('owner_dob')" max="{{ date('Y-m-d') }}" />
                            <x-input-error class="mt-2" :messages="$errors->get('owner_dob')" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="owner_gender" :value="__('Owner gender')" />
                                <select id="owner_gender" name="owner_gender" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                    <option value="">{{ __('Select gender') }}</option>
                                    @foreach (\App\Models\Business::OWNER_GENDERS as $gender)
                                        <option value="{{ $gender }}" @selected(old('owner_gender') === $gender)>{{ __(ucfirst($gender)) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('owner_gender')" />
                            </div>
                            <div>
                                <x-input-label for="owner_pwd_status" :value="__('Disability status')" />
                                <select id="owner_pwd_status" name="owner_pwd_status" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                    <option value="">{{ __('Select status') }}</option>
                                    @foreach (\App\Models\Business::OWNER_PWD_STATUSES as $pwdStatus)
                                        <option value="{{ $pwdStatus }}" @selected(old('owner_pwd_status') === $pwdStatus)>{{ __(ucfirst($pwdStatus)) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('owner_pwd_status')" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="owner_phone" :value="__('Owner phone')" />
                                <x-text-input id="owner_phone" name="owner_phone" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('owner_phone')" />
                                <x-input-error class="mt-2" :messages="$errors->get('owner_phone')" />
                            </div>
                            <div>
                                <x-input-label for="owner_email" :value="__('Owner email')" />
                                <x-text-input id="owner_email" name="owner_email" type="email" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('owner_email')" />
                                <x-input-error class="mt-2" :messages="$errors->get('owner_email')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="ownership_type" :value="__('Ownership type')" />
                            <select id="ownership_type" name="ownership_type" x-model="ownershipType" @change="if ((ownershipType === 'partnership' || ownershipType === 'cooperative' || ownershipType === 'company') && members.length === 0) addMember()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                <option value="">{{ __('Select type') }}</option>
                                @foreach (\App\Models\Business::OWNERSHIP_TYPES as $t)
                                    <option value="{{ $t }}" @selected(old('ownership_type') === $t)>{{ __(ucfirst(str_replace('_', ' ', $t))) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('ownership_type')" />
                        </div>

                        {{-- Partnership / cooperative / company members --}}
                        <div x-show="ownershipType === 'partnership' || ownershipType === 'cooperative' || ownershipType === 'company'" x-cloak class="space-y-4 pt-4 border-t border-slate-200">
                            <h4 class="text-sm font-semibold text-slate-700" x-text="ownershipType === 'partnership' ? '{{ __('Partnership members') }}' : (ownershipType === 'cooperative' ? '{{ __('Cooperative members') }}' : '{{ __('Company members') }}')"></h4>
                            <template x-for="(member, index) in members" :key="index">
                                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50/50 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-slate-600" x-text="'{{ __('Member') }} ' + (index + 1)"></span>
                                        <button type="button" @click="removeMember(index)" class="text-sm text-red-600 hover:text-red-700" x-show="members.length > 1">{{ __('Remove') }}</button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('First name') }}</label>
                                            <input type="text" :name="'members[' + index + '][first_name]'" x-model="member.first_name" class="block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Last name') }}</label>
                                            <input type="text" :name="'members[' + index + '][last_name]'" x-model="member.last_name" class="block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Phone number') }}</label>
                                            <input type="text" :name="'members[' + index + '][phone]'" x-model="member.phone" class="block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }}</label>
                                            <input type="email" :name="'members[' + index + '][email]'" x-model="member.email" class="block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Date of birth') }}</label>
                                        <input type="date" :name="'members[' + index + '][date_of_birth]'" x-model="member.date_of_birth" :max="maxDate" class="block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Gender') }}</label>
                                            <select :name="'members[' + index + '][gender]'" x-model="member.gender" class="block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                                <option value="">{{ __('Select gender') }}</option>
                                                @foreach (\App\Models\Business::OWNER_GENDERS as $gender)
                                                    <option value="{{ $gender }}">{{ __(ucfirst($gender)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Disability status') }}</label>
                                            <select :name="'members[' + index + '][pwd_status]'" x-model="member.pwd_status" class="block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                                <option value="">{{ __('Select status') }}</option>
                                                @foreach (\App\Models\Business::OWNER_PWD_STATUSES as $pwdStatus)
                                                    <option value="{{ $pwdStatus }}">{{ __(ucfirst($pwdStatus)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <button type="button" @click="addMember()" class="inline-flex items-center px-3 py-2 border border-dashed border-slate-300 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50">
                                {{ __('Add member') }}
                            </button>
                        </div>
                    </div>
                </div>
                @php
                    $createInitialMembers = array_values(old('members', [['first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '', 'date_of_birth' => '', 'gender' => '', 'pwd_status' => '']]));
                @endphp
                <script>
                    function ownershipForm() {
                        return {
                            ownershipType: '{{ old("ownership_type") }}' || '',
                            maxDate: '{{ date("Y-m-d") }}',
                            members: @json($createInitialMembers),
                            addMember() {
                                if (this.ownershipType === 'partnership' || this.ownershipType === 'cooperative' || this.ownershipType === 'company') {
                                    this.members.push({ first_name: '', last_name: '', phone: '', email: '', date_of_birth: '', gender: '', pwd_status: '' });
                                }
                            },
                            removeMember(i) {
                                if (this.members.length > 1) this.members.splice(i, 1);
                            }
                        };
                    }
                </script>

                {{-- Section 3: Business details --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80">
                        <h3 class="text-base font-semibold text-slate-800">{{ __('Business details') }}</h3>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Additional profile information for processor onboarding.') }}</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <x-input-label for="business_size" :value="__('Business size')" />
                            <select id="business_size" name="business_size" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                <option value="">{{ __('Select business size') }}</option>
                                <option value="micro" @selected(old('business_size') === 'micro')>{{ __('Micro (1-2 employees)') }}</option>
                                <option value="small" @selected(old('business_size') === 'small')>{{ __('Small (3-20 employees)') }}</option>
                                <option value="medium" @selected(old('business_size') === 'medium')>{{ __('Medium (21-100 employees)') }}</option>
                                <option value="large" @selected(old('business_size') === 'large')>{{ __('Large (100+ employees)') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('business_size')" />
                        </div>
                        <div>
                            <x-input-label for="baseline_revenue" :value="__('Baseline annual revenue (RWF)')" />
                            <select id="baseline_revenue" name="baseline_revenue" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                <option value="">{{ __('Select a range (optional)') }}</option>
                                @foreach (\App\Models\Business::baselineRevenueBracketOptions() as $bracket => $bracketLabel)
                                    <option value="{{ $bracket }}" @selected(old('baseline_revenue') === $bracket)>{{ $bracketLabel }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('baseline_revenue')" />
                        </div>
                    </div>
                </div>

                {{-- Section 4: VIBE metadata --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80">
                        <h3 class="text-base font-semibold text-slate-800">{{ __('VIBE metadata') }}</h3>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Tracking fields used by the VIBE pathway.') }}</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <x-input-label for="vibe_unique_id" :value="__('VIBE unique ID')" />
                            <x-text-input id="vibe_unique_id" name="vibe_unique_id" type="text" class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-100 text-gray-600" :value="old('vibe_unique_id')" readonly />
                            <p class="mt-1 text-xs text-slate-500">{{ __('Auto-generated after saving.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('vibe_unique_id')" />
                        </div>
                        <div>
                            <x-input-label for="vibe_commencement_date" :value="__('VIBE commencement date')" />
                            <x-text-input id="vibe_commencement_date" name="vibe_commencement_date" type="date" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" :value="old('vibe_commencement_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('vibe_commencement_date')" />
                        </div>
                        <div>
                            <x-input-label for="pathway_status" :value="__('Pathway status')" />
                            <select id="pathway_status" name="pathway_status" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                                @foreach (\App\Models\Business::PATHWAY_STATUSES as $pathwayStatus)
                                    <option value="{{ $pathwayStatus }}" @selected(old('pathway_status', 'active') === $pathwayStatus)>{{ __(ucfirst($pathwayStatus)) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('pathway_status')" />
                        </div>
                        <div>
                            <x-input-label for="vibe_comments" :value="__('VIBE comments')" />
                            <textarea id="vibe_comments" name="vibe_comments" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('vibe_comments') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('vibe_comments')" />
                        </div>
                    </div>
                </div>

                {{-- Section 5: Location info (dependent: country → province → district → sector → cell → village) --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60" x-data="locationDropdowns()" x-init="loadCountries()">
                    {{-- Hidden inputs so Alpine-bound values are submitted with the form --}}
                    <input type="hidden" name="country_id" :value="countryId || ''">
                    <input type="hidden" name="province_id" :value="provinceId || ''">
                    <input type="hidden" name="district_id" :value="districtId || ''">
                    <input type="hidden" name="sector_id" :value="sectorId || ''">
                    <input type="hidden" name="cell_id" :value="cellId || ''">
                    <input type="hidden" name="village_id" :value="villageId || ''">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80">
                        <h3 class="text-base font-semibold text-slate-800">{{ __('Location info') }}</h3>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Business address and administrative location.') }}</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <x-input-label for="country_id" :value="__('Country')" />
                            <select id="country_id" x-model="countryId" @change="onCountryChange()" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary shadow-sm" aria-required="true">
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
                            }
                        };
                    }
                </script>

                <div class="flex gap-4">
                    <button type="submit" id="register-business-btn" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy focus:bg-[#2563eb] active:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-bucha-primary focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Register Business') }}
                    </button>
                    <a href="{{ route('businesses.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
