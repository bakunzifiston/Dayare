<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Facility') }} — {{ $business->business_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('businesses.facilities.store', $business) }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="facility_name" :value="__('Facility Name')" />
                        <x-text-input id="facility_name" name="facility_name" type="text" class="mt-1 block w-full" :value="old('facility_name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('facility_name')" />
                    </div>

                    <div>
                        <x-input-label for="facility_type" :value="__('Facility Type')" />
                        <select id="facility_type" name="facility_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @foreach (\App\Models\Facility::TYPES as $t)
                                <option value="{{ $t }}" @selected(old('facility_type') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_type')" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="district" :value="__('District')" />
                            <x-text-input id="district" name="district" type="text" class="mt-1 block w-full" :value="old('district')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('district')" />
                        </div>
                        <div>
                            <x-input-label for="sector" :value="__('Sector')" />
                            <x-text-input id="sector" name="sector" type="text" class="mt-1 block w-full" :value="old('sector')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('sector')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="gps" :value="__('GPS Coordinates')" />
                        <x-text-input id="gps" name="gps" type="text" class="mt-1 block w-full" :value="old('gps')" placeholder="e.g. -1.9536, 30.0606" />
                        <x-input-error class="mt-2" :messages="$errors->get('gps')" />
                    </div>

                    <div>
                        <x-input-label for="license_number" :value="__('License Number')" />
                        <x-text-input id="license_number" name="license_number" type="text" class="mt-1 block w-full" :value="old('license_number')" />
                        <x-input-error class="mt-2" :messages="$errors->get('license_number')" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="license_issue_date" :value="__('License Issue Date')" />
                            <x-text-input id="license_issue_date" name="license_issue_date" type="date" class="mt-1 block w-full" :value="old('license_issue_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('license_issue_date')" />
                        </div>
                        <div>
                            <x-input-label for="license_expiry_date" :value="__('License Expiry Date')" />
                            <x-text-input id="license_expiry_date" name="license_expiry_date" type="date" class="mt-1 block w-full" :value="old('license_expiry_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('license_expiry_date')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="daily_capacity" :value="__('Daily Capacity')" />
                        <x-text-input id="daily_capacity" name="daily_capacity" type="number" min="0" class="mt-1 block w-full" :value="old('daily_capacity')" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('e.g. animals per day or kg') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('daily_capacity')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach (\App\Models\Facility::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', 'active') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Add Facility') }}</x-primary-button>
                        <a href="{{ route('businesses.facilities.index', $business) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
