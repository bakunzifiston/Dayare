@php
    $pdfValue = function (string $key) use ($pdfDefaults, $savedPdfDetails) {
        return old(
            "pdf_details.{$key}",
            $savedPdfDetails[$key] ?? ($pdfDefaults[$key] ?? '')
        );
    };
@endphp

<div id="pdf-details" class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 space-y-6">
    <div>
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Certificate PDF details') }}</h3>
        <p class="mt-1 text-xs text-slate-600">
            {{ __('These fields appear on the downloaded Nyagatare veterinary certificate. Values are pre-filled from your batch, client, cold room, and transport data — edit any field before saving.') }}
        </p>
    </div>

    <div class="space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">1. {{ __('Slaughterhouse') }}</h4>
        <div>
            <x-input-label for="pdf_details_facility_location" :value="__('Location (District, Sector, Cell)')" />
            <x-text-input id="pdf_details_facility_location" name="pdf_details[facility_location]" type="text" class="mt-1 block w-full" :value="$pdfValue('facility_location')" />
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <x-input-label for="pdf_details_facility_type" :value="__('Type')" />
                <x-text-input id="pdf_details_facility_type" name="pdf_details[facility_type]" type="text" class="mt-1 block w-full" :value="$pdfValue('facility_type')" />
            </div>
            <div>
                <x-input-label for="pdf_details_facility_phone" :value="__('Telephone')" />
                <x-text-input id="pdf_details_facility_phone" name="pdf_details[facility_phone]" type="text" class="mt-1 block w-full" :value="$pdfValue('facility_phone')" />
            </div>
        </div>
        <div>
            <x-input-label for="pdf_details_facility_registration" :value="__('Registration No.')" />
            <x-text-input id="pdf_details_facility_registration" name="pdf_details[facility_registration]" type="text" class="mt-1 block w-full" :value="$pdfValue('facility_registration')" />
        </div>
    </div>

    <div class="space-y-3 border-t border-slate-200 pt-4">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">2. {{ __('Animal identification') }}</h4>
        <div>
            <x-input-label for="pdf_details_animal_names" :value="__('Names (ear tags / animal names)')" />
            <x-text-input id="pdf_details_animal_names" name="pdf_details[animal_names]" type="text" class="mt-1 block w-full" :value="$pdfValue('animal_names')" />
        </div>
        <div>
            <x-input-label for="pdf_details_butcher_name" :value="__('Butcher\'s name')" />
            <x-text-input id="pdf_details_butcher_name" name="pdf_details[butcher_name]" type="text" class="mt-1 block w-full" :value="$pdfValue('butcher_name')" />
        </div>
        <div>
            <x-input-label for="pdf_details_selling_location" :value="__('Selling location (District, Sector, Cell)')" />
            <x-text-input id="pdf_details_selling_location" name="pdf_details[selling_location]" type="text" class="mt-1 block w-full" :value="$pdfValue('selling_location')" />
        </div>
        <div>
            <x-input-label for="pdf_details_owner_phone" :value="__('Telephone')" />
            <x-text-input id="pdf_details_owner_phone" name="pdf_details[owner_phone]" type="text" class="mt-1 block w-full" :value="$pdfValue('owner_phone')" />
        </div>
    </div>

    <div class="space-y-3 border-t border-slate-200 pt-4">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">3. {{ __('Butcher / meat selling shop') }}</h4>
        <div>
            <x-input-label for="pdf_details_shop_name" :value="__('Names')" />
            <x-text-input id="pdf_details_shop_name" name="pdf_details[shop_name]" type="text" class="mt-1 block w-full" :value="$pdfValue('shop_name')" />
        </div>
        <div>
            <x-input-label for="pdf_details_shop_phone" :value="__('Telephone')" />
            <x-text-input id="pdf_details_shop_phone" name="pdf_details[shop_phone]" type="text" class="mt-1 block w-full" :value="$pdfValue('shop_phone')" />
        </div>
    </div>

    <div class="space-y-3 border-t border-slate-200 pt-4">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">4. {{ __('Meat weight and temperature') }}</h4>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <x-input-label for="pdf_details_carcass_meat_kg" :value="__('Carcass meat (kg)')" />
                <x-text-input id="pdf_details_carcass_meat_kg" name="pdf_details[carcass_meat_kg]" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="$pdfValue('carcass_meat_kg')" />
            </div>
            <div>
                <x-input-label for="pdf_details_other_meat_kg" :value="__('Other meat (kg)')" />
                <x-text-input id="pdf_details_other_meat_kg" name="pdf_details[other_meat_kg]" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="$pdfValue('other_meat_kg')" />
            </div>
            <div>
                <x-input-label for="pdf_details_temperature_celsius" :value="__('Temperature (°C)')" />
                <x-text-input id="pdf_details_temperature_celsius" name="pdf_details[temperature_celsius]" type="number" step="0.1" class="mt-1 block w-full" :value="$pdfValue('temperature_celsius')" />
            </div>
        </div>
    </div>

    <div class="space-y-3 border-t border-slate-200 pt-4">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">5. {{ __('Authorized meat transporter') }}</h4>
        <div>
            <x-input-label for="pdf_details_transporter_license_holder" :value="__('Name of license holder')" />
            <x-text-input id="pdf_details_transporter_license_holder" name="pdf_details[transporter_license_holder]" type="text" class="mt-1 block w-full" :value="$pdfValue('transporter_license_holder')" />
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <x-input-label for="pdf_details_vehicle_plate_number" :value="__('Vehicle plate number')" />
                <x-text-input id="pdf_details_vehicle_plate_number" name="pdf_details[vehicle_plate_number]" type="text" class="mt-1 block w-full" :value="$pdfValue('vehicle_plate_number')" />
            </div>
            <div>
                <x-input-label for="pdf_details_driver_name" :value="__('Driver\'s name')" />
                <x-text-input id="pdf_details_driver_name" name="pdf_details[driver_name]" type="text" class="mt-1 block w-full" :value="$pdfValue('driver_name')" />
            </div>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <x-input-label for="pdf_details_departure_destination" :value="__('Departure destination')" />
                <x-text-input id="pdf_details_departure_destination" name="pdf_details[departure_destination]" type="text" class="mt-1 block w-full" :value="$pdfValue('departure_destination')" />
            </div>
            <div>
                <x-input-label for="pdf_details_transporter_phone" :value="__('Telephone')" />
                <x-text-input id="pdf_details_transporter_phone" name="pdf_details[transporter_phone]" type="text" class="mt-1 block w-full" :value="$pdfValue('transporter_phone')" />
            </div>
        </div>
    </div>
</div>
