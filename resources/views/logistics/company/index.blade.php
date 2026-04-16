@php
    $registeredBusinessIds = $companies->pluck('business_id')->map(fn ($id) => (int) $id)->all();
    $availableBusinesses = $logisticsBusinesses->reject(
        fn ($business) => in_array((int) $business->id, $registeredBusinessIds, true)
    );
@endphp

@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    @slot('actions')
        <a href="#company-form" class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ $actionLabel }}</a>
    @endslot

    <div class="space-y-4">
        <section id="company-form" class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Register logistics company') }}</h2>
            @if ($availableBusinesses->isEmpty())
                <p class="text-sm text-slate-600">
                    {{ __('All accessible logistics businesses already have a registered company.') }}
                </p>
            @else
                <form method="POST" action="{{ route('logistics.company.store') }}" class="grid gap-2 md:grid-cols-3">
                    @csrf
                    <select name="business_id" class="rounded-md border-slate-300 text-sm" required>
                        <option value="">{{ __('Select logistics business') }}</option>
                        @foreach ($availableBusinesses as $business)
                            <option value="{{ $business->id }}" @selected((int) old('business_id') === (int) $business->id)>{{ $business->business_name }}</option>
                        @endforeach
                    </select>
                    <input name="name" value="{{ old('name') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Company name') }}" required>
                    <input name="registration_number" value="{{ old('registration_number') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Registration number') }}" required>
                    <input name="tax_id" value="{{ old('tax_id') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Tax ID') }}">
                    <input name="license_type" value="{{ old('license_type') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('License type') }}" required>
                    <input type="date" name="license_expiry_date" value="{{ old('license_expiry_date') }}" class="rounded-md border-slate-300 text-sm" required>
                    <input name="contact_person" value="{{ old('contact_person') }}" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Contact person') }}">
                    <div class="md:col-span-3">
                        <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save company') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <x-logistics.table
            :columns="[__('Company'), __('Registration'), __('License expiry'), __('Contact')]"
            :has-rows="$companies->isNotEmpty()"
            :empty-message="__('No companies found')"
        >
            @foreach ($companies as $company)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $company->name }}</td>
                    <td class="px-4 py-3">{{ $company->registration_number }}</td>
                    <td class="px-4 py-3">{{ optional($company->license_expiry_date)->toDateString() }}</td>
                    <td class="px-4 py-3">{{ $company->contact_person ?: '-' }}</td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
