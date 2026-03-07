<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">
            {{ __('Edit client') }}: {{ $client->name }}
        </h1>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('clients.update', $client) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Basic information') }}</h2>
                <div>
                    <x-input-label for="business_id" :value="__('Business')" />
                    <select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                        <option value="">{{ __('Select business') }}</option>
                        @foreach ($businesses as $b)
                            <option value="{{ $b->id }}" @selected(old('business_id', $client->business_id) == $b->id)>{{ $b->business_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('business_id')" />
                </div>
                <div>
                    <x-input-label for="name" :value="__('Client name / Company name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $client->name)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="contact_person" :value="__('Contact person (optional)')" />
                    <x-text-input id="contact_person" name="contact_person" type="text" class="mt-1 block w-full" :value="old('contact_person', $client->contact_person)" />
                    <x-input-error class="mt-2" :messages="$errors->get('contact_person')" />
                </div>
                <div class="flex items-center gap-2">
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-[#3B82F6] focus:ring-[#3B82F6]" @checked(old('is_active', $client->is_active)) />
                    <label for="is_active" class="text-sm text-slate-700">{{ __('Active') }}</label>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Contact') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $client->email)" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $client->phone)" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Address') }}</h2>
                <div>
                    <x-input-label for="country" :value="__('Country')" />
                    <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="old('country', $client->country)" required placeholder="e.g. Rwanda, Uganda, Kenya" />
                    <x-input-error class="mt-2" :messages="$errors->get('country')" />
                </div>
                <div>
                    <x-input-label for="address_line_1" :value="__('Address line 1')" />
                    <x-text-input id="address_line_1" name="address_line_1" type="text" class="mt-1 block w-full" :value="old('address_line_1', $client->address_line_1)" />
                    <x-input-error class="mt-2" :messages="$errors->get('address_line_1')" />
                </div>
                <div>
                    <x-input-label for="address_line_2" :value="__('Address line 2')" />
                    <x-text-input id="address_line_2" name="address_line_2" type="text" class="mt-1 block w-full" :value="old('address_line_2', $client->address_line_2)" />
                    <x-input-error class="mt-2" :messages="$errors->get('address_line_2')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="city" :value="__('City')" />
                        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $client->city)" />
                        <x-input-error class="mt-2" :messages="$errors->get('city')" />
                    </div>
                    <div>
                        <x-input-label for="state_region" :value="__('State / Region')" />
                        <x-text-input id="state_region" name="state_region" type="text" class="mt-1 block w-full" :value="old('state_region', $client->state_region)" />
                        <x-input-error class="mt-2" :messages="$errors->get('state_region')" />
                    </div>
                    <div>
                        <x-input-label for="postal_code" :value="__('Postal code')" />
                        <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full" :value="old('postal_code', $client->postal_code)" />
                        <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Optional') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tax_id" :value="__('Tax ID')" />
                        <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full" :value="old('tax_id', $client->tax_id)" />
                        <x-input-error class="mt-2" :messages="$errors->get('tax_id')" />
                    </div>
                    <div>
                        <x-input-label for="registration_number" :value="__('Registration number')" />
                        <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full" :value="old('registration_number', $client->registration_number)" />
                        <x-input-error class="mt-2" :messages="$errors->get('registration_number')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="business_type" :value="__('Business type')" />
                        <select id="business_type" name="business_type" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                            <option value="">{{ __('None') }}</option>
                            @foreach (\App\Models\Client::BUSINESS_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('business_type', $client->business_type) === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="preferred_facility_id" :value="__('Preferred facility')" />
                        <select id="preferred_facility_id" name="preferred_facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($facilities ?? [] as $f)
                                <option value="{{ $f->id }}" @selected(old('preferred_facility_id', $client->preferred_facility_id) == $f->id)>{{ $f->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="preferred_species" :value="__('Preferred species')" />
                    <x-text-input id="preferred_species" name="preferred_species" type="text" class="mt-1 block w-full" :value="old('preferred_species', $client->preferred_species)" placeholder="e.g. Cattle, Goat" />
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('notes', $client->notes) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Update client') }}
                </button>
                <a href="{{ route('clients.show', $client) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
