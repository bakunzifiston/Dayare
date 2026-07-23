<?php

namespace App\Support;

use App\Models\Facility;
use Carbon\Carbon;

class CertificatePdfDetails
{
    /** @var list<string> */
    public const KEYS = [
        'facility_location',
        'facility_type',
        'facility_phone',
        'facility_registration',
        'animal_names',
        'species',
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
        'destination_country',
        'destination_address',
        'departure_time',
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
            "{$prefix}.species" => $string,
            "{$prefix}.butcher_name" => $string,
            "{$prefix}.selling_location" => $string,
            "{$prefix}.owner_phone" => $string,
            "{$prefix}.shop_name" => $string,
            "{$prefix}.shop_phone" => $string,
            "{$prefix}.carcass_meat_kg" => ['nullable', 'numeric', 'min:0', 'max:999999'],
            "{$prefix}.other_meat_kg" => ['nullable', 'numeric', 'min:0', 'max:999999'],
            "{$prefix}.temperature_celsius" => ['nullable', 'numeric', 'min:-50', 'max:50'],
            "{$prefix}.transporter_license_holder" => $string,
            "{$prefix}.vehicle_plate_number" => $string,
            "{$prefix}.driver_name" => $string,
            "{$prefix}.departure_destination" => $string,
            "{$prefix}.destination_country" => $string,
            "{$prefix}.destination_address" => ['nullable', 'string', 'max:500'],
            "{$prefix}.departure_time" => ['nullable', 'string', 'max:32', 'date_format:d/m/Y H:i'],
            "{$prefix}.transporter_phone" => $string,
        ];
    }

    /**
     * Value for <input type="datetime-local"> from stored PDF detail text.
     */
    public static function departureTimeInputValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        foreach (['Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $raw);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d\TH:i');
                }
            } catch (\Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d\TH:i');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Calendar date from stored certificate departure time, if parseable.
     */
    public static function departureTimeDate(mixed $value): ?Carbon
    {
        $input = self::departureTimeInputValue($value);
        if ($input === '') {
            return null;
        }

        try {
            return Carbon::parse($input)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Store departure as a readable date+time for the certificate PDF.
     */
    public static function formatDepartureTimeForStorage(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        foreach (['Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $raw);
                if ($parsed !== false) {
                    return $parsed->format('d/m/Y H:i');
                }
            } catch (\Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($raw)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $raw;
        }
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

            if ($key === 'departure_time') {
                $formatted = self::formatDepartureTimeForStorage($value);
                if ($formatted !== null) {
                    $normalized[$key] = $formatted;
                }

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

    public static function facilityLocationIsComplete(?Facility $facility): bool
    {
        if ($facility === null) {
            return false;
        }

        $facility->loadMissing(['districtDivision', 'sectorDivision', 'cell']);

        return self::nonEmptyString($facility->districtDivision?->name ?? $facility->getRawOriginal('district')) !== null
            && self::nonEmptyString($facility->sectorDivision?->name ?? $facility->getRawOriginal('sector')) !== null
            && self::nonEmptyString($facility->cell?->name) !== null;
    }

    private static function nonEmptyString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
