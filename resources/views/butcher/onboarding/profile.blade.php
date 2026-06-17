<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.onboarding.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Onboarding') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Business profile') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('butcher.onboarding.partials.progress', ['progress' => $progress])

            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('butcher.onboarding.profile.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-5">
                @csrf

                <div>
                    <x-input-label for="business_name" :value="__('Shop / business name')" />
                    <x-text-input id="business_name" name="business_name" type="text" class="mt-1 block w-full" :value="old('business_name', $business->business_name)" required />
                    <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="butchery_type" :value="__('Butchery type')" />
                    <select id="butchery_type" name="butchery_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach (\App\Models\Business::BUTCHERY_TYPES as $type)
                            <option value="{{ $type }}" @selected(old('butchery_type', $business->butchery_type) === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('butchery_type')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="rdb_registration_number" :value="__('RDB registration number')" />
                        <x-text-input id="rdb_registration_number" name="rdb_registration_number" type="text" class="mt-1 block w-full" :value="old('rdb_registration_number', $business->hasPlaceholderRegistration() ? '' : $business->registration_number)" required />
                        <x-input-error :messages="$errors->get('rdb_registration_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="tin_number" :value="__('TIN number (10 digits)')" />
                        <x-text-input id="tin_number" name="tin_number" type="text" class="mt-1 block w-full" maxlength="10" :value="old('tin_number', $business->tax_id)" required />
                        <x-input-error :messages="$errors->get('tin_number')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Phone (+2507xxxxxxxx)')" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" placeholder="+250788123456" :value="old('phone', $business->contact_phone === '0000000000' ? '' : $business->contact_phone)" required />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="rfa_permit_number" :value="__('RFA permit number (optional)')" />
                        <x-text-input id="rfa_permit_number" name="rfa_permit_number" type="text" class="mt-1 block w-full" :value="old('rfa_permit_number', $business->rfa_permit_number)" />
                        <x-input-error :messages="$errors->get('rfa_permit_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="rfa_permit_expiry" :value="__('RFA permit expiry (optional)')" />
                        <x-text-input id="rfa_permit_expiry" name="rfa_permit_expiry" type="date" class="mt-1 block w-full" :value="old('rfa_permit_expiry', optional($business->rfa_permit_expiry)->format('Y-m-d'))" />
                        <x-input-error :messages="$errors->get('rfa_permit_expiry')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <x-input-label for="district" :value="__('District')" />
                        <select id="district" name="district" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('Select district') }}</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district }}" @selected(old('district', $business->butcher_district) === $district)>{{ $district }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('district')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="sector" :value="__('Sector')" />
                        <x-text-input id="sector" name="sector" type="text" class="mt-1 block w-full" :value="old('sector', $business->butcher_sector)" />
                        <x-input-error :messages="$errors->get('sector')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="cell" :value="__('Cell')" />
                        <x-text-input id="cell" name="cell" type="text" class="mt-1 block w-full" :value="old('cell', $business->butcher_cell)" />
                        <x-input-error :messages="$errors->get('cell')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="gps_lat" :value="__('GPS latitude (optional)')" />
                        <x-text-input id="gps_lat" name="gps_lat" type="text" class="mt-1 block w-full" :value="old('gps_lat', $business->gps_lat)" />
                        <x-input-error :messages="$errors->get('gps_lat')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="gps_lng" :value="__('GPS longitude (optional)')" />
                        <x-text-input id="gps_lng" name="gps_lng" type="text" class="mt-1 block w-full" :value="old('gps_lng', $business->gps_lng)" />
                        <x-input-error :messages="$errors->get('gps_lng')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Save and continue') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
