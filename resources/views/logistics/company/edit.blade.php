@php
    use App\Models\LogisticsCompany;

    $c = $logisticsCompany;
    $oldMembers = old('members');
    if (is_array($oldMembers) && count($oldMembers) > 0) {
        $defaultMembers = $oldMembers;
    } elseif ($c->members->isNotEmpty()) {
        $defaultMembers = $c->members->map(fn ($m) => [
            'first_name' => $m->first_name,
            'last_name' => $m->last_name,
            'phone' => $m->phone,
            'email' => $m->email,
        ])->values()->all();
    } else {
        $defaultMembers = [['first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '']];
    }
@endphp

@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    @slot('actions')
        <a
            href="{{ route('logistics.company.show', $c) }}?company_id={{ $c->id }}"
            class="rounded-md border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
            {{ __('Cancel') }}
        </a>
    @endslot

    <div
        x-data="logisticsCompanyRegistrationForm(
            @js(old('company_type', $c->company_type)),
            @js($defaultMembers),
            @js(LogisticsCompany::TYPE_SHARED_COMPANY)
        )"
    >
        <form method="POST" action="{{ route('logistics.company.update', $c) }}" class="grid gap-3 md:grid-cols-3">
            @csrf
            @method('PUT')
            <select name="business_id" class="rounded-md border-slate-300 text-sm" required>
                <option value="">{{ __('Select logistics business') }}</option>
                @foreach ($logisticsBusinesses as $business)
                    <option value="{{ $business->id }}" @selected((int) old('business_id', $c->business_id) === (int) $business->id)>{{ $business->business_name }}</option>
                @endforeach
            </select>
            <select
                name="company_type"
                x-model="companyType"
                @change="onCompanyTypeChange()"
                class="rounded-md border-slate-300 text-sm md:col-span-2"
                required
            >
                <option value="{{ LogisticsCompany::TYPE_INDIVIDUAL }}">{{ __('Individual') }}</option>
                <option value="{{ LogisticsCompany::TYPE_SHARED_COMPANY }}">{{ __('Shared Company') }}</option>
            </select>
            <input name="name" value="{{ old('name', $c->name) }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Company name') }}" required>
            <input name="registration_number" value="{{ old('registration_number', $c->registration_number) }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Registration number') }}" required>
            <input name="tax_id" value="{{ old('tax_id', $c->tax_id) }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Tax ID') }}">
            <input name="license_type" value="{{ old('license_type', $c->license_type) }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('License type') }}" required>
            <input type="date" name="license_expiry_date" value="{{ old('license_expiry_date', optional($c->license_expiry_date)->toDateString()) }}" class="rounded-md border-slate-300 text-sm" required>
            <input name="contact_person" value="{{ old('contact_person', $c->contact_person) }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Contact person') }}">

            @include('partials.logistics-company-location', [
                'countryId' => old('country_id', $c->country_id),
                'provinceId' => old('province_id', $c->province_id),
                'districtId' => old('district_id', $c->district_id),
                'sectorId' => old('sector_id', $c->sector_id),
                'cellId' => old('cell_id', $c->cell_id),
                'villageId' => old('village_id', $c->village_id),
            ])

            <div
                class="md:col-span-3"
                x-show="companyType === sharedCompanyType"
                x-transition
            >
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Members') }}</h3>
                        <button type="button" class="text-xs font-semibold text-[#7A1C22] hover:underline" @click="addMember()">{{ __('Add member') }}</button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ __('At least one member is required for a shared company. Phone and email must be unique among members.') }}</p>
                    <div class="mt-4 space-y-4">
                        <template x-for="(member, index) in members" :key="index">
                            <div class="grid gap-2 rounded-md border border-slate-200 bg-white p-3 md:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('First name') }}</label>
                                    <input type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm" x-model="member.first_name" :name="'members[' + index + '][first_name]'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Last name') }}</label>
                                    <input type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm" x-model="member.last_name" :name="'members[' + index + '][last_name]'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Phone number') }}</label>
                                    <input type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm" x-model="member.phone" :name="'members[' + index + '][phone]'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Email address') }}</label>
                                    <input type="email" class="mt-1 w-full rounded-md border-slate-300 text-sm" x-model="member.email" :name="'members[' + index + '][email]'">
                                </div>
                                <div class="md:col-span-2 lg:col-span-4 flex justify-end">
                                    <button type="button" class="text-xs text-rose-600 hover:underline" @click="removeMember(index)" x-show="members.length > 1">{{ __('Remove') }}</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="md:col-span-3">
                <button type="submit" class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save changes') }}</button>
            </div>
        </form>
    </div>
@endcomponent
