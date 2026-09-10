@php
    $registrationValue = old(
        'registration_number',
        str_starts_with((string) $business->registration_number, 'PENDING-') ? '' : $business->registration_number
    );
    $phoneValue = old(
        'contact_phone',
        $business->contact_phone === '0000000000' ? '' : $business->contact_phone
    );
    $progressPercent = is_array($progress ?? null) ? ($progress['percent'] ?? null) : ($progress ?? null);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Business profile') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Shop details shown on certificates, deliveries, and customer-facing traceability.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($progressPercent !== null)
                <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-bucha">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Onboarding progress') }}</p>
                        <p class="text-sm font-semibold text-bucha-primary">{{ $progressPercent }}%</p>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-bucha-primary" style="width: {{ min(100, max(0, (int) $progressPercent)) }}%"></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold">
                        <a href="{{ route('butcher.outlets.index') }}" class="text-bucha-primary hover:underline">{{ __('Manage outlets') }}</a>
                        <a href="{{ route('butcher.permits.index') }}" class="text-bucha-primary hover:underline">{{ __('Manage permits') }}</a>
                    </div>
                </div>
            @endif

            <form method="post" action="{{ route('butcher.business.update') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="business_name" :value="__('Shop / business name')" />
                    <x-text-input id="business_name" name="business_name" type="text" class="mt-1 block w-full" :value="old('business_name', $business->business_name)" required />
                    <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="butchery_type" :value="__('Butchery type')" />
                    <select id="butchery_type" name="butchery_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach (\App\Models\Business::BUTCHERY_TYPES as $type)
                            <option value="{{ $type }}" @selected(old('butchery_type', $business->butchery_type) === $type)>
                                {{ str_replace('_', ' ', ucfirst($type)) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('butchery_type')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="registration_number" :value="__('Registration number')" />
                        <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full" :value="$registrationValue" required />
                        <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="tax_id" :value="__('Tax ID (TIN)')" />
                        <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full" placeholder="1234567890" :value="old('tax_id', $business->tax_id)" required />
                        <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="contact_phone" :value="__('Contact phone')" />
                        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full" placeholder="+250788123456" :value="$phoneValue" required />
                        <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $business->email)" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="address_line_1" :value="__('Street address')" />
                    <x-text-input id="address_line_1" name="address_line_1" type="text" class="mt-1 block w-full" :value="old('address_line_1', $business->address_line_1)" />
                    <x-input-error :messages="$errors->get('address_line_1')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="city" :value="__('City / town')" />
                    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $business->city)" />
                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="rfa_permit_number" :value="__('RFA permit number')" />
                        <x-text-input id="rfa_permit_number" name="rfa_permit_number" type="text" class="mt-1 block w-full" :value="old('rfa_permit_number', $business->rfa_permit_number)" />
                        <x-input-error :messages="$errors->get('rfa_permit_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="rfa_permit_expiry" :value="__('RFA permit expiry')" />
                        <x-text-input
                            id="rfa_permit_expiry"
                            name="rfa_permit_expiry"
                            type="date"
                            class="mt-1 block w-full"
                            :value="old('rfa_permit_expiry', optional($business->rfa_permit_expiry)->format('Y-m-d'))"
                        />
                        <x-input-error :messages="$errors->get('rfa_permit_expiry')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <x-input-label for="butcher_district" :value="__('District')" />
                        <select id="butcher_district" name="butcher_district" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('Select district') }}</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district }}" @selected(old('butcher_district', $business->butcher_district) === $district)>{{ $district }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('butcher_district')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="butcher_sector" :value="__('Sector')" />
                        <x-text-input id="butcher_sector" name="butcher_sector" type="text" class="mt-1 block w-full" :value="old('butcher_sector', $business->butcher_sector)" />
                        <x-input-error :messages="$errors->get('butcher_sector')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="butcher_cell" :value="__('Cell')" />
                        <x-text-input id="butcher_cell" name="butcher_cell" type="text" class="mt-1 block w-full" :value="old('butcher_cell', $business->butcher_cell)" />
                        <x-input-error :messages="$errors->get('butcher_cell')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="gps_lat" :value="__('GPS latitude')" />
                        <x-text-input id="gps_lat" name="gps_lat" type="number" step="any" class="mt-1 block w-full" :value="old('gps_lat', $business->gps_lat)" />
                        <x-input-error :messages="$errors->get('gps_lat')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="gps_lng" :value="__('GPS longitude')" />
                        <x-text-input id="gps_lng" name="gps_lng" type="number" step="any" class="mt-1 block w-full" :value="old('gps_lng', $business->gps_lng)" />
                        <x-input-error :messages="$errors->get('gps_lng')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <x-input-label for="butcher_fresh_max_temp_c" :value="__('Fresh max temp (°C)')" />
                        <x-text-input id="butcher_fresh_max_temp_c" name="butcher_fresh_max_temp_c" type="number" step="0.1" class="mt-1 block w-full" :value="old('butcher_fresh_max_temp_c', $business->butcher_fresh_max_temp_c ?? 4)" required />
                        <x-input-error :messages="$errors->get('butcher_fresh_max_temp_c')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="butcher_frozen_max_temp_c" :value="__('Frozen max temp (°C)')" />
                        <x-text-input id="butcher_frozen_max_temp_c" name="butcher_frozen_max_temp_c" type="number" step="0.1" class="mt-1 block w-full" :value="old('butcher_frozen_max_temp_c', $business->butcher_frozen_max_temp_c ?? -18)" required />
                        <x-input-error :messages="$errors->get('butcher_frozen_max_temp_c')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="butcher_batch_shelf_life_days" :value="__('Batch shelf life (days)')" />
                        <x-text-input id="butcher_batch_shelf_life_days" name="butcher_batch_shelf_life_days" type="number" min="1" class="mt-1 block w-full" :value="old('butcher_batch_shelf_life_days', $business->butcher_batch_shelf_life_days ?? 3)" required />
                        <x-input-error :messages="$errors->get('butcher_batch_shelf_life_days')" class="mt-2" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <div class="flex flex-wrap gap-3 text-xs font-semibold">
                        <a href="{{ route('butcher.outlets.index') }}" class="text-bucha-primary hover:underline">{{ __('Outlets') }}</a>
                        <a href="{{ route('butcher.permits.index') }}" class="text-bucha-primary hover:underline">{{ __('Permits') }}</a>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('butcher.dashboard') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Back to dashboard') }}
                        </a>
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                            {{ __('Save profile') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
