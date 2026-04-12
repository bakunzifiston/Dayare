<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('processor.supply-requests.index') }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Supply requests') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Request animals from a farmer') }}</h2>
    </x-slot>

    <div class="max-w-xl">
        <form method="post" action="{{ route('processor.supply-requests.store') }}" class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="processor_business_id" :value="__('Your processor business')" />
                <select name="processor_business_id" id="processor_business_id" required class="mt-1 block w-full rounded-lg border-gray-300">
                    @foreach ($processorBusinesses as $b)
                        <option value="{{ $b->id }}" @selected(old('processor_business_id') == $b->id)>{{ $b->business_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('processor_business_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="farmer_id" :value="__('Farmer business')" />
                <select name="farmer_id" id="farmer_id" required class="mt-1 block w-full rounded-lg border-gray-300">
                    @foreach ($farmers as $f)
                        <option value="{{ $f->id }}" @selected(old('farmer_id') == $f->id)>{{ $f->business_name }} ({{ $f->registration_number }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('farmer_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="destination_facility_id" :value="__('Receiving facility')" />
                <select name="destination_facility_id" id="destination_facility_id" required class="mt-1 block w-full rounded-lg border-gray-300">
                    @foreach ($facilities as $fac)
                        <option value="{{ $fac->id }}" @selected(old('destination_facility_id') == $fac->id)>{{ $fac->facility_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('destination_facility_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="animal_type" :value="__('Animal type')" />
                <select name="animal_type" id="animal_type" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Support\FarmerAnimalType::ALL as $t)
                        <option value="{{ $t }}" @selected(old('animal_type') === $t)>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="quantity_requested" :value="__('Quantity requested')" />
                <x-text-input id="quantity_requested" name="quantity_requested" type="number" min="1" class="mt-1 block w-full" :value="old('quantity_requested', 1)" required />
                <x-input-error :messages="$errors->get('quantity_requested')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="preferred_date" :value="__('Preferred date (optional)')" />
                <x-text-input id="preferred_date" name="preferred_date" type="date" class="mt-1 block w-full" :value="old('preferred_date')" />
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Send request') }}</button>
        </form>
    </div>
</x-app-layout>
