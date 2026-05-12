@php
    $buyer = $buyer ?? null;
@endphp

<div class="space-y-6">
    @if (! empty($businesses))
        <div>
            <x-input-label for="business_id" :value="__('Farmer business')" />
            <select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                @foreach ($businesses as $business)
                    <option value="{{ $business->id }}" @selected((int) old('business_id', $buyer?->business_id) === $business->id)>{{ $business->business_name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('business_id')" class="mt-2" />
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="buyer_name" :value="__('Buyer name')" />
            <x-text-input id="buyer_name" name="buyer_name" type="text" class="mt-1 block w-full" :value="old('buyer_name', $buyer?->buyer_name)" required />
            <x-input-error :messages="$errors->get('buyer_name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="buyer_type" :value="__('Buyer type')" />
            <select id="buyer_type" name="buyer_type" class="mt-1 block w-full rounded-lg border-gray-300" required>
                @foreach (\App\Models\Buyer::TYPES as $type)
                    <option value="{{ $type }}" @selected(old('buyer_type', $buyer?->buyer_type) === $type)>{{ __(ucwords(str_replace('_', ' ', $type))) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('buyer_type')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="contact_person" :value="__('Contact person')" />
            <x-text-input id="contact_person" name="contact_person" type="text" class="mt-1 block w-full" :value="old('contact_person', $buyer?->contact_person)" />
        </div>
        <div>
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $buyer?->phone)" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $buyer?->email)" />
        </div>
        <div>
            <x-input-label for="national_id" :value="__('National ID')" />
            <x-text-input id="national_id" name="national_id" type="text" class="mt-1 block w-full" :value="old('national_id', $buyer?->national_id)" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="company_registration" :value="__('Company registration')" />
            <x-text-input id="company_registration" name="company_registration" type="text" class="mt-1 block w-full" :value="old('company_registration', $buyer?->company_registration)" />
        </div>
        <div>
            <x-input-label for="preferred_payment_method" :value="__('Preferred payment method')" />
            <select id="preferred_payment_method" name="preferred_payment_method" class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="">{{ __('Select method') }}</option>
                @foreach (\App\Models\Sale::PAYMENT_METHODS as $method)
                    <option value="{{ $method }}" @selected(old('preferred_payment_method', $buyer?->preferred_payment_method) === $method)>{{ __(ucwords(str_replace('_', ' ', $method))) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="country" :value="__('Country')" />
            <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="old('country', $buyer?->country)" />
        </div>
        <div>
            <x-input-label for="province" :value="__('Province')" />
            <x-text-input id="province" name="province" type="text" class="mt-1 block w-full" :value="old('province', $buyer?->province)" />
        </div>
        <div>
            <x-input-label for="district" :value="__('District')" />
            <x-text-input id="district" name="district" type="text" class="mt-1 block w-full" :value="old('district', $buyer?->district)" />
        </div>
    </div>

    <div>
        <x-input-label for="address" :value="__('Address')" />
        <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('address', $buyer?->address) }}</textarea>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="trust_level" :value="__('Trust level')" />
            <select id="trust_level" name="trust_level" class="mt-1 block w-full rounded-lg border-gray-300" required>
                @foreach (\App\Models\Buyer::TRUST_LEVELS as $level)
                    <option value="{{ $level }}" @selected(old('trust_level', $buyer?->trust_level ?? \App\Models\Buyer::TRUST_NEW) === $level)>{{ __(ucwords(str_replace('_', ' ', $level))) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                @foreach (\App\Models\Buyer::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $buyer?->status ?? \App\Models\Buyer::STATUS_ACTIVE) === $status)>{{ __(ucfirst($status)) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <x-input-label for="notes" :value="__('Notes')" />
        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('notes', $buyer?->notes) }}</textarea>
    </div>
</div>
