<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Business') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('businesses.update', $business) }}" class="space-y-6">
                    @csrf
                    @method('patch')

                    <div>
                        <x-input-label for="business_name" :value="__('Business Name')" />
                        <x-text-input id="business_name" name="business_name" type="text" class="mt-1 block w-full" :value="old('business_name', $business->business_name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('business_name')" />
                    </div>

                    <div>
                        <x-input-label for="registration_number" :value="__('Registration Number')" />
                        <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full" :value="old('registration_number', $business->registration_number)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('registration_number')" />
                    </div>

                    <div>
                        <x-input-label for="tax_id" :value="__('Tax ID')" />
                        <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full" :value="old('tax_id', $business->tax_id)" />
                        <x-input-error class="mt-2" :messages="$errors->get('tax_id')" />
                    </div>

                    <div>
                        <x-input-label for="contact_phone" :value="__('Contact Phone')" />
                        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full" :value="old('contact_phone', $business->contact_phone)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('contact_phone')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $business->email)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach (\App\Models\Business::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', $business->status) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update Business') }}</x-primary-button>
                        <a href="{{ route('businesses.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
