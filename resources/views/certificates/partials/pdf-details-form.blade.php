@php
    $pdfValue = function (string $key) use ($pdfDefaults, $savedPdfDetails) {
        return old(
            "pdf_details.{$key}",
            $savedPdfDetails[$key] ?? ($pdfDefaults[$key] ?? '')
        );
    };
@endphp

<div id="pdf-details" class="space-y-6">
    <x-wizard-section :title="__('Livestock owner')">
        <x-wizard-field for="pdf_details_butcher_name" :label="__('Owner name')">
            <input id="pdf_details_butcher_name" name="pdf_details[butcher_name]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('butcher_name') }}" />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('pdf_details.butcher_name')" />

        <x-wizard-field for="pdf_details_selling_location" :label="__('Owner location (District, Sector, Cell)')" required>
            <input id="pdf_details_selling_location" name="pdf_details[selling_location]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('selling_location') }}" required />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('pdf_details.selling_location')" />

        <x-wizard-field for="pdf_details_owner_phone" :label="__('Telephone')">
            <input id="pdf_details_owner_phone" name="pdf_details[owner_phone]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('owner_phone') }}" />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('pdf_details.owner_phone')" />
    </x-wizard-section>

    <x-wizard-section :title="__('Animal identification')">
        <div class="bucha-wizard-grid">
            <div>
                <x-wizard-field for="pdf_details_species" :label="__('Species')">
                    <input id="pdf_details_species" name="pdf_details[species]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('species') }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.species')" />
            </div>
            <div>
                <x-wizard-field for="pdf_details_animal_names" :label="__('Ear tag numbers')" required>
                    <input id="pdf_details_animal_names" name="pdf_details[animal_names]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('animal_names') }}" required />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.animal_names')" />
            </div>
        </div>
    </x-wizard-section>

    <x-wizard-section :title="__('Butcher / meat selling shop')">
        <x-wizard-field for="pdf_details_shop_name" :label="__('Names')">
            <input id="pdf_details_shop_name" name="pdf_details[shop_name]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('shop_name') }}" />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('pdf_details.shop_name')" />

        <x-wizard-field for="pdf_details_shop_phone" :label="__('Telephone')">
            <input id="pdf_details_shop_phone" name="pdf_details[shop_phone]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('shop_phone') }}" />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('pdf_details.shop_phone')" />
    </x-wizard-section>

    <x-wizard-section :title="__('Meat weight and temperature')">
        <div class="bucha-wizard-grid--3">
            <div>
                <x-wizard-field for="pdf_details_carcass_meat_kg" :label="__('Carcass meat (kg)')" required>
                    <input id="pdf_details_carcass_meat_kg" name="pdf_details[carcass_meat_kg]" type="number" step="0.01" min="0.01" class="bucha-wizard-input" value="{{ $pdfValue('carcass_meat_kg') }}" required />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.carcass_meat_kg')" />
            </div>
            <div>
                <x-wizard-field for="pdf_details_other_meat_kg" :label="__('Other meat (kg)')">
                    <input id="pdf_details_other_meat_kg" name="pdf_details[other_meat_kg]" type="number" step="0.01" min="0" class="bucha-wizard-input" value="{{ $pdfValue('other_meat_kg') }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.other_meat_kg')" />
            </div>
            <div>
                <x-wizard-field for="pdf_details_temperature_celsius" :label="__('Temperature (°C)')">
                    <input id="pdf_details_temperature_celsius" name="pdf_details[temperature_celsius]" type="number" step="0.1" min="-50" max="50" class="bucha-wizard-input" value="{{ $pdfValue('temperature_celsius') }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.temperature_celsius')" />
            </div>
        </div>
    </x-wizard-section>

    <x-wizard-section :title="__('Authorized meat transporter')">
        <x-wizard-field for="pdf_details_transporter_license_holder" :label="__('Name of license holder')">
            <input id="pdf_details_transporter_license_holder" name="pdf_details[transporter_license_holder]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('transporter_license_holder') }}" />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('pdf_details.transporter_license_holder')" />

        <div class="bucha-wizard-grid">
            <div>
                <x-wizard-field for="pdf_details_vehicle_plate_number" :label="__('Vehicle plate number')">
                    <input id="pdf_details_vehicle_plate_number" name="pdf_details[vehicle_plate_number]" type="text" class="bucha-wizard-input font-mono tracking-wide" value="{{ $pdfValue('vehicle_plate_number') }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.vehicle_plate_number')" />
            </div>
            <div>
                <x-wizard-field for="pdf_details_driver_name" :label="__('Driver\'s name')">
                    <input id="pdf_details_driver_name" name="pdf_details[driver_name]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('driver_name') }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.driver_name')" />
            </div>
        </div>

        <div class="bucha-wizard-grid">
            <div>
                <x-wizard-field for="pdf_details_departure_time" :label="__('Departure time / date')">
                    <input
                        id="pdf_details_departure_time"
                        name="pdf_details[departure_time]"
                        type="datetime-local"
                        class="bucha-wizard-input"
                        value="{{ \App\Support\CertificatePdfDetails::departureTimeInputValue($pdfValue('departure_time')) }}"
                    />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.departure_time')" />
            </div>
            <div>
                <x-wizard-field for="pdf_details_transporter_phone" :label="__('Telephone')">
                    <input id="pdf_details_transporter_phone" name="pdf_details[transporter_phone]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('transporter_phone') }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.transporter_phone')" />
            </div>
        </div>

        <x-wizard-field for="pdf_details_departure_destination" :label="__('Destination')">
            <input id="pdf_details_departure_destination" name="pdf_details[departure_destination]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('departure_destination') }}" />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('pdf_details.departure_destination')" />

        <div class="bucha-wizard-grid">
            <div>
                <x-wizard-field for="pdf_details_destination_country" :label="__('Country')">
                    <input id="pdf_details_destination_country" name="pdf_details[destination_country]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('destination_country') }}" placeholder="e.g. RW, KE, UG" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.destination_country')" />
            </div>
            <div>
                <x-wizard-field for="pdf_details_destination_address" :label="__('Address (optional)')">
                    <input id="pdf_details_destination_address" name="pdf_details[destination_address]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('destination_address') }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.destination_address')" />
            </div>
        </div>
    </x-wizard-section>

    <x-wizard-section :title="__('Slaughterhouse details')">
        <x-wizard-field for="pdf_details_facility_location" :label="__('Location (District, Sector, Cell)')">
            <input id="pdf_details_facility_location" name="pdf_details[facility_location]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('facility_location') }}" />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('pdf_details.facility_location')" />

        <div class="bucha-wizard-grid">
            <div>
                <x-wizard-field for="pdf_details_facility_type" :label="__('Type')">
                    <input id="pdf_details_facility_type" name="pdf_details[facility_type]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('facility_type') }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.facility_type')" />
            </div>
            <div>
                <x-wizard-field for="pdf_details_facility_phone" :label="__('Telephone')">
                    <input id="pdf_details_facility_phone" name="pdf_details[facility_phone]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('facility_phone') }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('pdf_details.facility_phone')" />
            </div>
        </div>

        <x-wizard-field for="pdf_details_facility_registration" :label="__('Registration No.')">
            <input id="pdf_details_facility_registration" name="pdf_details[facility_registration]" type="text" class="bucha-wizard-input" value="{{ $pdfValue('facility_registration') }}" />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('pdf_details.facility_registration')" />
    </x-wizard-section>
</div>
