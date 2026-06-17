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
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
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
                    <x-input-label for="registration_number" :value="__('Registration number')" />
                    <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full" :value="old('registration_number', str_starts_with((string) $business->registration_number, 'PENDING-') ? '' : $business->registration_number)" required />
                    <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="contact_phone" :value="__('Contact phone')" />
                        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full" :value="old('contact_phone', $business->contact_phone === '0000000000' ? '' : $business->contact_phone)" required />
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

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('butcher.dashboard') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('Back to dashboard') }}
                    </a>
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Save profile') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
