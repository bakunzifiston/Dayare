<?php

namespace App\Services\Processor;

use App\Models\Certificate;
use App\Models\TransportTrip;
use App\Support\CertificatePdfDetails;

class CertificateTransportDefaultsService
{
    public const PDF_VEHICLE_PLATE = 'vehicle_plate_number';

    public const PDF_DRIVER_NAME = 'driver_name';

    public const PDF_TRANSPORTER_PHONE = 'transporter_phone';

    public const PDF_DEPARTURE_DESTINATION = 'departure_destination';

    public const PDF_DESTINATION_COUNTRY = 'destination_country';

    public const PDF_DESTINATION_ADDRESS = 'destination_address';

    public const PDF_DEPARTURE_TIME = 'departure_time';

    public const PDF_LICENSE_HOLDER = 'transporter_license_holder';

    /**
     * @return array{
     *     vehicle_plate_number: string|null,
     *     driver_name: string|null,
     *     driver_phone: string|null,
     *     destination_name: string|null,
     *     destination_country: string|null,
     *     destination_address: string|null,
     *     departure_date: string|null,
     *     departure_destination: string|null,
     *     transporter_license_holder: string|null
     * }
     */
    public function suggestedForCertificate(Certificate $certificate): array
    {
        $certificate->loadMissing([
            'transportTrips' => fn ($query) => $query->orderByDesc('departure_date'),
        ]);

        $pdf = is_array($certificate->pdf_details) ? $certificate->pdf_details : [];
        $latestTrip = $certificate->transportTrips->first();
        $destinationName = $this->firstNonEmpty(
            $pdf[self::PDF_DEPARTURE_DESTINATION] ?? null,
            $latestTrip?->destination_name,
        );
        $departureDate = CertificatePdfDetails::departureTimeDate($pdf[self::PDF_DEPARTURE_TIME] ?? null)
            ?->toDateString()
            ?? $latestTrip?->departure_date?->toDateString();

        return [
            'vehicle_plate_number' => $this->firstNonEmpty(
                $pdf[self::PDF_VEHICLE_PLATE] ?? null,
                $latestTrip?->vehicle_plate_number,
            ),
            'driver_name' => $this->firstNonEmpty(
                $pdf[self::PDF_DRIVER_NAME] ?? null,
                $latestTrip?->driver_name,
            ),
            'driver_phone' => $this->firstNonEmpty(
                $pdf[self::PDF_TRANSPORTER_PHONE] ?? null,
                $latestTrip?->driver_phone,
            ),
            'destination_name' => $destinationName,
            'destination_country' => $this->firstNonEmpty(
                $pdf[self::PDF_DESTINATION_COUNTRY] ?? null,
                $latestTrip?->destination_country,
            ),
            'destination_address' => $this->firstNonEmpty(
                $pdf[self::PDF_DESTINATION_ADDRESS] ?? null,
                $latestTrip?->destination_address,
            ),
            'departure_date' => $departureDate,
            'departure_destination' => $destinationName,
            'transporter_license_holder' => $this->firstNonEmpty(
                $pdf[self::PDF_LICENSE_HOLDER] ?? null,
                $latestTrip?->driver_name,
            ),
        ];
    }

    /**
     * Trip fields fixed by certificate pdf_details (user should not re-type these).
     *
     * @return array<string, string>
     */
    public function lockedTripFields(Certificate $certificate): array
    {
        $pdf = is_array($certificate->pdf_details) ? $certificate->pdf_details : [];
        $locked = [];

        if ($this->nonEmpty($pdf[self::PDF_VEHICLE_PLATE] ?? null)) {
            $locked['vehicle_plate_number'] = trim((string) $pdf[self::PDF_VEHICLE_PLATE]);
        }

        if ($this->nonEmpty($pdf[self::PDF_DRIVER_NAME] ?? null)) {
            $locked['driver_name'] = trim((string) $pdf[self::PDF_DRIVER_NAME]);
        }

        if ($this->nonEmpty($pdf[self::PDF_TRANSPORTER_PHONE] ?? null)) {
            $locked['driver_phone'] = trim((string) $pdf[self::PDF_TRANSPORTER_PHONE]);
        }

        if ($this->nonEmpty($pdf[self::PDF_DEPARTURE_DESTINATION] ?? null)) {
            $locked['destination_name'] = trim((string) $pdf[self::PDF_DEPARTURE_DESTINATION]);
        }

        if ($this->nonEmpty($pdf[self::PDF_DESTINATION_COUNTRY] ?? null)) {
            $locked['destination_country'] = trim((string) $pdf[self::PDF_DESTINATION_COUNTRY]);
        }

        if ($this->nonEmpty($pdf[self::PDF_DESTINATION_ADDRESS] ?? null)) {
            $locked['destination_address'] = trim((string) $pdf[self::PDF_DESTINATION_ADDRESS]);
        }

        $departureDate = CertificatePdfDetails::departureTimeDate($pdf[self::PDF_DEPARTURE_TIME] ?? null);
        if ($departureDate !== null) {
            $locked['departure_date'] = $departureDate->toDateString();
        }

        return $locked;
    }

    /**
     * @return list<string>
     */
    public function lockedFieldKeys(Certificate $certificate): array
    {
        return array_keys($this->lockedTripFields($certificate));
    }

    public function syncTripToCertificate(Certificate $certificate, TransportTrip $trip): void
    {
        $pdf = is_array($certificate->pdf_details) ? $certificate->pdf_details : [];

        $this->assignPdfValue($pdf, self::PDF_VEHICLE_PLATE, $trip->vehicle_plate_number);
        $this->assignPdfValue($pdf, self::PDF_DRIVER_NAME, $trip->driver_name);
        $this->assignPdfValue($pdf, self::PDF_TRANSPORTER_PHONE, $trip->driver_phone);
        $this->assignPdfValue($pdf, self::PDF_DEPARTURE_DESTINATION, $trip->destination_name);
        $this->assignPdfValue($pdf, self::PDF_DESTINATION_COUNTRY, $trip->destination_country);
        $this->assignPdfValue($pdf, self::PDF_DESTINATION_ADDRESS, $trip->destination_address);
        $this->assignPdfValue($pdf, self::PDF_LICENSE_HOLDER, $trip->driver_name);

        if ($trip->departure_date !== null && ! $this->nonEmpty($pdf[self::PDF_DEPARTURE_TIME] ?? null)) {
            $pdf[self::PDF_DEPARTURE_TIME] = $trip->departure_date->format('d/m/Y').' 00:00';
        }

        $certificate->update(['pdf_details' => $pdf]);
    }

    /**
     * @param  array<string, mixed>  $pdf
     */
    private function assignPdfValue(array &$pdf, string $key, mixed $value): void
    {
        if (! $this->nonEmpty($value)) {
            return;
        }

        $pdf[$key] = trim((string) $value);
    }

    private function firstNonEmpty(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if ($this->nonEmpty($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function nonEmpty(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }
}
