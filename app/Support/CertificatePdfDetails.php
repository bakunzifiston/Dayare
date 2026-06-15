<?php

namespace App\Support;

class CertificatePdfDetails
{
    /** @var list<string> */
    public const KEYS = [
        'facility_location',
        'facility_type',
        'facility_phone',
        'facility_registration',
        'animal_names',
        'butcher_name',
        'selling_location',
        'owner_phone',
        'shop_name',
        'shop_phone',
        'carcass_meat_kg',
        'other_meat_kg',
        'temperature_celsius',
        'transporter_license_holder',
        'vehicle_plate_number',
        'driver_name',
        'departure_destination',
        'transporter_phone',
    ];

    /**
     * @return array<string, list<string|\Illuminate\Validation\Rules\In>>
     */
    public static function validationRules(string $prefix = 'pdf_details'): array
    {
        $string = ['nullable', 'string', 'max:255'];
        $numeric = ['nullable', 'numeric', 'min:0'];

        return [
            $prefix => ['nullable', 'array'],
            "{$prefix}.facility_location" => $string,
            "{$prefix}.facility_type" => $string,
            "{$prefix}.facility_phone" => $string,
            "{$prefix}.facility_registration" => $string,
            "{$prefix}.animal_names" => $string,
            "{$prefix}.butcher_name" => $string,
            "{$prefix}.selling_location" => $string,
            "{$prefix}.owner_phone" => $string,
            "{$prefix}.shop_name" => $string,
            "{$prefix}.shop_phone" => $string,
            "{$prefix}.carcass_meat_kg" => $numeric,
            "{$prefix}.other_meat_kg" => $numeric,
            "{$prefix}.temperature_celsius" => ['nullable', 'numeric', 'min:-50', 'max:50'],
            "{$prefix}.transporter_license_holder" => $string,
            "{$prefix}.vehicle_plate_number" => $string,
            "{$prefix}.driver_name" => $string,
            "{$prefix}.departure_destination" => $string,
            "{$prefix}.transporter_phone" => $string,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @return array<string, mixed>|null
     */
    public static function normalize(?array $input): ?array
    {
        if ($input === null) {
            return null;
        }

        $normalized = [];
        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            $value = $input[$key];
            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($key, ['carcass_meat_kg', 'other_meat_kg', 'temperature_celsius'], true)) {
                $normalized[$key] = is_numeric($value) ? (float) $value : $value;
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                $normalized[$key] = $trimmed;
            }
        }

        return $normalized === [] ? null : $normalized;
    }
}
