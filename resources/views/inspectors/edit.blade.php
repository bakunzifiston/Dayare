<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Inspector') }} — {{ $inspector->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('inspectors.update', $inspector) }}" class="space-y-8">
                    @csrf
                    @method('put')

                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Assigned Facility') }}</h3>
                        <div>
                            <x-input-label for="facility_id" :value="__('Facility')" />
                            <select id="facility_id" name="facility_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                @foreach ($facilities as $f)
                                    <option value="{{ $f['id'] }}" @selected(old('facility_id', $inspector->facility_id) == $f['id'])>{{ $f['label'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Personal information') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="first_name" :value="__('First name')" />
                                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $inspector->first_name)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                            </div>
                            <div>
                                <x-input-label for="last_name" :value="__('Last name')" />
                                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $inspector->last_name)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="national_id" :value="__('National ID')" />
                            <x-text-input id="national_id" name="national_id" type="text" class="mt-1 block w-full" :value="old('national_id', $inspector->national_id)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('national_id')" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="phone_number" :value="__('Phone number')" />
                                <x-text-input id="phone_number" name="phone_number" type="text" class="mt-1 block w-full" :value="old('phone_number', $inspector->phone_number)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
                            </div>
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $inspector->email)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="dob" :value="__('Date of birth')" />
                                <x-text-input id="dob" name="dob" type="date" class="mt-1 block w-full" :value="old('dob', $inspector->dob?->format('Y-m-d'))" required />
                                <x-input-error class="mt-2" :messages="$errors->get('dob')" />
                            </div>
                            <div>
                                <x-input-label for="nationality" :value="__('Nationality')" />
                                <x-text-input id="nationality" name="nationality" type="text" class="mt-1 block w-full" :value="old('nationality', $inspector->nationality)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('nationality')" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Location') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="country" :value="__('Country')" />
                                <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="old('country', $inspector->country)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('country')" />
                            </div>
                            <div>
                                <x-input-label for="district" :value="__('District')" />
                                <x-text-input id="district" name="district" type="text" class="mt-1 block w-full" :value="old('district', $inspector->district)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('district')" />
                            </div>
                            <div>
                                <x-input-label for="sector" :value="__('Sector')" />
                                <x-text-input id="sector" name="sector" type="text" class="mt-1 block w-full" :value="old('sector', $inspector->sector)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('sector')" />
                            </div>
                            <div>
                                <x-input-label for="cell" :value="__('Cell')" />
                                <x-text-input id="cell" name="cell" type="text" class="mt-1 block w-full" :value="old('cell', $inspector->cell)" />
                                <x-input-error class="mt-2" :messages="$errors->get('cell')" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="village" :value="__('Village')" />
                                <x-text-input id="village" name="village" type="text" class="mt-1 block w-full" :value="old('village', $inspector->village)" />
                                <x-input-error class="mt-2" :messages="$errors->get('village')" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Authorization') }}</h3>
                        <div>
                            <x-input-label for="authorization_number" :value="__('Authorization number')" />
                            <x-text-input id="authorization_number" name="authorization_number" type="text" class="mt-1 block w-full" :value="old('authorization_number', $inspector->authorization_number)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('authorization_number')" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="authorization_issue_date" :value="__('Authorization issue date')" />
                                <x-text-input id="authorization_issue_date" name="authorization_issue_date" type="date" class="mt-1 block w-full" :value="old('authorization_issue_date', $inspector->authorization_issue_date?->format('Y-m-d'))" required />
                                <x-input-error class="mt-2" :messages="$errors->get('authorization_issue_date')" />
                            </div>
                            <div>
                                <x-input-label for="authorization_expiry_date" :value="__('Authorization expiry date')" />
                                <x-text-input id="authorization_expiry_date" name="authorization_expiry_date" type="date" class="mt-1 block w-full" :value="old('authorization_expiry_date', $inspector->authorization_expiry_date?->format('Y-m-d'))" required />
                                <x-input-error class="mt-2" :messages="$errors->get('authorization_expiry_date')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="species_allowed" :value="__('Species allowed')" />
                            <x-text-input id="species_allowed" name="species_allowed" type="text" class="mt-1 block w-full" :value="old('species_allowed', $inspector->species_allowed)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('species_allowed')" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="daily_capacity" :value="__('Daily capacity')" />
                                <x-text-input id="daily_capacity" name="daily_capacity" type="number" min="0" class="mt-1 block w-full" :value="old('daily_capacity', $inspector->daily_capacity)" />
                                <x-input-error class="mt-2" :messages="$errors->get('daily_capacity')" />
                            </div>
                            <div>
                                <x-input-label for="stamp_serial_number" :value="__('Stamp serial number')" />
                                <x-text-input id="stamp_serial_number" name="stamp_serial_number" type="text" class="mt-1 block w-full" :value="old('stamp_serial_number', $inspector->stamp_serial_number)" />
                                <x-input-error class="mt-2" :messages="$errors->get('stamp_serial_number')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach (\App\Models\Inspector::STATUSES as $s)
                                    <option value="{{ $s }}" @selected(old('status', $inspector->status) === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update Inspector') }}</x-primary-button>
                        <a href="{{ route('inspectors.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
