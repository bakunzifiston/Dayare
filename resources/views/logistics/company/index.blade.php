@php
    use App\Models\LogisticsCompany;

    $formFieldKeys = [
        'business_id',
        'company_type',
        'name',
        'registration_number',
        'tax_id',
        'license_type',
        'license_expiry_date',
        'contact_person',
        'country_id',
    ];
    $oldMembers = old('members');
    $defaultMembers = is_array($oldMembers) && count($oldMembers) > 0
        ? $oldMembers
        : [['first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '']];
    $showCompanyForm = collect($formFieldKeys)->contains(fn ($key) => old($key) !== null || $errors->has($key))
        || $errors->has('members')
        || $errors->has('country_id');
@endphp

@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    @slot('actions')
        <button
            type="button"
            class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]"
            x-on:click="$dispatch('logistics-register-company-open')"
        >
            {{ $actionLabel }}
        </button>
    @endslot

    <div class="space-y-4">
        <div
            x-data="{ showForm: @js($showCompanyForm) }"
            x-on:logistics-register-company-open.window="showForm = true; $nextTick(() => document.getElementById('company-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
        >
            <section id="company-form" x-show="showForm" x-transition class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Register logistics company') }}</h2>
                @if ($logisticsBusinesses->isEmpty())
                    <p class="text-sm text-slate-600">
                        {{ __('No logistics business is available for your account.') }}
                    </p>
                @else
                    <div
                        x-data="logisticsCompanyRegistrationForm(
                            @js(old('company_type', LogisticsCompany::TYPE_INDIVIDUAL)),
                            @js($defaultMembers),
                            @js(LogisticsCompany::TYPE_SHARED_COMPANY)
                        )"
                    >
                        <form method="POST" action="{{ route('logistics.company.store') }}" class="grid gap-3 md:grid-cols-3">
                            @csrf
                            <select name="business_id" class="rounded-md border-slate-300 text-sm" required>
                                <option value="">{{ __('Select logistics business') }}</option>
                                @foreach ($logisticsBusinesses as $business)
                                    <option value="{{ $business->id }}" @selected((int) old('business_id') === (int) $business->id)>{{ $business->business_name }}</option>
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
                            <input name="name" value="{{ old('name') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Company name') }}" required>
                            <input name="registration_number" value="{{ old('registration_number') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Registration number') }}" required>
                            <input name="tax_id" value="{{ old('tax_id') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Tax ID') }}">
                            <input name="license_type" value="{{ old('license_type') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('License type') }}" required>
                            <input type="date" name="license_expiry_date" value="{{ old('license_expiry_date') }}" class="rounded-md border-slate-300 text-sm" required>
                            <input name="contact_person" value="{{ old('contact_person') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Contact person') }}">

                            @include('partials.logistics-company-location')

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
                                <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save company') }}</button>
                            </div>
                        </form>
                    </div>
                @endif
            </section>
        </div>

        <x-logistics.table
            :columns="[__('Company'), __('Type'), __('Registration'), __('License expiry'), __('Contact'), __('Actions')]"
            :has-rows="$companies->isNotEmpty()"
            :empty-message="__('No companies found')"
        >
            @foreach ($companies as $company)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $company->name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">
                        @if ($company->company_type === LogisticsCompany::TYPE_SHARED_COMPANY)
                            {{ __('Shared Company') }}
                        @else
                            {{ __('Individual') }}
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $company->registration_number }}</td>
                    <td class="px-4 py-3">{{ optional($company->license_expiry_date)->toDateString() }}</td>
                    <td class="px-4 py-3">{{ $company->contact_person ?: '-' }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('logistics.company.show', $company) }}?company_id={{ $company->id }}" class="text-xs font-medium text-[#7A1C22] hover:underline">{{ __('View') }}</a>
                        <span class="mx-1 text-slate-300">|</span>
                        <a href="{{ route('logistics.company.edit', $company) }}?company_id={{ $company->id }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                        <span class="mx-1 text-slate-300">|</span>
                        <form method="POST" action="{{ route('logistics.company.destroy', $company) }}" class="inline" onsubmit="return confirm(@json(__('Are you sure you want to delete this company? Related vehicles, orders, and trips may also be removed.')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
