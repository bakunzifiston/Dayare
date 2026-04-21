<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Register inspector') }}</h1>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <form method="POST" action="{{ route('inspectors.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-200 pb-2">{{ __('Profile') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                            <option value="">{{ __('Select facility') }}</option>
                            @foreach ($facilities as $facility)
                                <option value="{{ $facility['id'] }}" @selected((int) old('facility_id') === (int) $facility['id'])>{{ $facility['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>
                    <div>
                        <x-input-label for="first_name" :value="__('First name')" />
                        <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                    </div>
                    <div>
                        <x-input-label for="last_name" :value="__('Last name')" />
                        <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                    </div>
                    <div>
                        <x-input-label for="national_id" :value="__('National ID')" />
                        <x-text-input id="national_id" name="national_id" type="text" class="mt-1 block w-full" :value="old('national_id')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('national_id')" />
                    </div>
                    <div>
                        <x-input-label for="phone_number" :value="__('Phone number')" />
                        <x-text-input id="phone_number" name="phone_number" type="text" class="mt-1 block w-full" :value="old('phone_number')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="dob" :value="__('Date of birth')" />
                        <x-text-input id="dob" name="dob" type="date" class="mt-1 block w-full" :value="old('dob')" max="{{ date('Y-m-d') }}" required />
                        <x-input-error class="mt-2" :messages="$errors->get('dob')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="nationality" :value="__('Nationality')" />
                        <x-text-input id="nationality" name="nationality" type="text" class="mt-1 block w-full" :value="old('nationality')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('nationality')" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-200 pb-2">{{ __('Authorization') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="authorization_number" :value="__('Authorization number')" />
                        <x-text-input id="authorization_number" name="authorization_number" type="text" class="mt-1 block w-full" :value="old('authorization_number')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('authorization_number')" />
                    </div>
                    <div>
                        <x-input-label for="stamp_serial_number" :value="__('Stamp serial number')" />
                        <x-text-input id="stamp_serial_number" name="stamp_serial_number" type="text" class="mt-1 block w-full" :value="old('stamp_serial_number')" />
                        <x-input-error class="mt-2" :messages="$errors->get('stamp_serial_number')" />
                    </div>
                    <div>
                        <x-input-label for="authorization_issue_date" :value="__('Issue date')" />
                        <x-text-input id="authorization_issue_date" name="authorization_issue_date" type="date" class="mt-1 block w-full" :value="old('authorization_issue_date')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('authorization_issue_date')" />
                    </div>
                    <div>
                        <x-input-label for="authorization_expiry_date" :value="__('Expiry date')" />
                        <x-text-input id="authorization_expiry_date" name="authorization_expiry_date" type="date" class="mt-1 block w-full" :value="old('authorization_expiry_date')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('authorization_expiry_date')" />
                    </div>
                    <div>
                        <x-input-label for="daily_capacity" :value="__('Daily capacity')" />
                        <x-text-input id="daily_capacity" name="daily_capacity" type="number" min="0" class="mt-1 block w-full" :value="old('daily_capacity')" />
                        <x-input-error class="mt-2" :messages="$errors->get('daily_capacity')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                            @foreach (\App\Models\Inspector::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-200 pb-2">{{ __('Species allowed') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($species as $entry)
                        @php $name = (string) $entry->name; @endphp
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="species_allowed[]" value="{{ $name }}" class="rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary" @checked(in_array($name, old('species_allowed', []), true))>
                            <span>{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('species_allowed')" />
                <x-input-error class="mt-2" :messages="$errors->get('species_allowed.*')" />
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Register inspector') }}
                </button>
                <a href="{{ route('inspectors.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
