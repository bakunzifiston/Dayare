<?php

namespace App\Services\SuperAdmin;

use App\Models\Certificate;
use App\Models\TransportTrip;

class RicaSupplyChainDestinationResolver
{
    /**
     * @return array{
     *     label: string,
     *     district: string|null,
     *     district_id: int|null,
     *     sector: string|null,
     *     other: string|null
     * }
     */
    public function resolveForTrip(TransportTrip $trip): array
    {
        $certificate = $trip->certificate;
        if ($certificate === null) {
            return $this->fallbackFromTrip($trip);
        }

        $destination = $this->resolveCertificateDestination($certificate, $trip);
        $label = $this->destinationLabel($destination, $trip);

        return array_merge($destination, ['label' => $label]);
    }

    /**
     * @return array{
     *     label: string,
     *     district: string|null,
     *     district_id: int|null,
     *     sector: string|null,
     *     other: string|null
     * }
     */
    public function resolveForCertificate(Certificate $certificate): array
    {
        $trip = $certificate->transportTrips->sortByDesc('departure_date')->first();
        $destination = $this->resolveCertificateDestination($certificate, $trip);
        $label = $this->destinationLabel($destination, $trip);

        return array_merge($destination, ['label' => $label]);
    }

    public function resolveTripQtyKg(TransportTrip $trip): float
    {
        $confirmation = $trip->deliveryConfirmation;
        if ($confirmation !== null && strtolower((string) $confirmation->received_unit) === 'kg') {
            return round((float) $confirmation->received_quantity, 2);
        }

        $certificate = $trip->certificate;
        if ($certificate === null) {
            return round((float) ($trip->batch?->quantity ?? 0), 2);
        }

        $releasedKgByCertificate = $certificate->warehouseStorages
            ->whereNotNull('released_date')
            ->groupBy('certificate_id')
            ->map(fn ($storages) => $storages->sum('quantity_stored'))
            ->all();

        $releasedKgByBatch = $certificate->warehouseStorages
            ->whereNotNull('released_date')
            ->groupBy('batch_id')
            ->map(fn ($storages) => $storages->sum('quantity_stored'))
            ->all();

        return $this->resolveCertificateQtyKg($certificate, $releasedKgByCertificate, $releasedKgByBatch);
    }

    /**
     * @return array{district: string|null, district_id: int|null, sector: string|null, other: string|null}
     */
    private function resolveCertificateDestination(Certificate $certificate, ?TransportTrip $trip = null): array
    {
        $trip ??= $certificate->transportTrips->sortByDesc('departure_date')->first();

        if ($trip?->destinationFacility) {
            $facility = $trip->destinationFacility;
            $other = trim((string) ($facility->cell?->name ?? $facility->getRawOriginal('cell') ?? ''));
            if ($other === '') {
                $other = $this->nullablePdfDetail($certificate, 'shop_name')
                    ?? $this->nullablePdfDetail($certificate, 'departure_destination');
            }

            return [
                'district' => $facility->districtDivision?->name ?? $facility->getRawOriginal('district'),
                'district_id' => $facility->district_id ? (int) $facility->district_id : null,
                'sector' => $facility->sectorDivision?->name ?? $facility->getRawOriginal('sector'),
                'other' => $other ?: null,
            ];
        }

        if ($trip && $trip->isExternalDestination()) {
            $confirmation = $trip->deliveryConfirmation;
            $client = $confirmation?->client;
            if ($client) {
                $client->loadMissing(['districtDivision', 'sectorDivision', 'cell']);

                return [
                    'district' => $client->districtDivision?->name,
                    'district_id' => $client->district_id ? (int) $client->district_id : null,
                    'sector' => $client->sectorDivision?->name,
                    'other' => trim(collect([
                        $trip->destination_name,
                        $client->cell?->name,
                        $client->address_line_1,
                    ])->filter(fn (?string $part) => $part !== null && $part !== '')->first() ?? '')
                        ?: $trip->destination_name,
                ];
            }

            $addressLine = trim((string) ($trip->destination_address ?? ''));
            if ($addressLine !== '') {
                $parsed = $this->parseLocationLine($addressLine);
                if ($parsed['district'] || $parsed['sector'] || $parsed['other']) {
                    if (! $parsed['other']) {
                        $parsed['other'] = $trip->destination_name;
                    }

                    return $parsed;
                }
            }

            return [
                'district' => null,
                'district_id' => null,
                'sector' => null,
                'other' => $trip->destination_name ?: $trip->destination_display,
            ];
        }

        $sellingLocation = $this->nullablePdfDetail($certificate, 'selling_location');
        if ($sellingLocation) {
            $parsed = $this->parseLocationLine($sellingLocation);
            if (! $parsed['other']) {
                $parsed['other'] = $this->nullablePdfDetail($certificate, 'shop_name')
                    ?? $this->nullablePdfDetail($certificate, 'departure_destination');
            }

            return $parsed;
        }

        $departureDestination = $this->nullablePdfDetail($certificate, 'departure_destination');
        if ($departureDestination) {
            $parsed = $this->parseLocationLine($departureDestination);
            if ($parsed['district'] || $parsed['sector']) {
                if (! $parsed['other']) {
                    $parsed['other'] = $this->nullablePdfDetail($certificate, 'shop_name');
                }

                return $parsed;
            }

            return [
                'district' => null,
                'district_id' => null,
                'sector' => null,
                'other' => $departureDestination,
            ];
        }

        $shopName = $this->nullablePdfDetail($certificate, 'shop_name');
        if ($shopName) {
            return [
                'district' => null,
                'district_id' => null,
                'sector' => null,
                'other' => $shopName,
            ];
        }

        return [
            'district' => null,
            'district_id' => null,
            'sector' => null,
            'other' => null,
        ];
    }

    /**
     * @param  array{district: string|null, district_id: int|null, sector: string|null, other: string|null}  $destination
     */
    private function destinationLabel(array $destination, ?TransportTrip $trip): string
    {
        if ($trip?->destinationFacility) {
            return $trip->destinationFacility->facility_name;
        }

        if ($trip && filled($trip->destination_name)) {
            return (string) $trip->destination_name;
        }

        if (filled($destination['other'])) {
            return (string) $destination['other'];
        }

        if (filled($destination['district'])) {
            return (string) $destination['district'];
        }

        return __('Unknown destination');
    }

    /**
     * @return array{district: string|null, district_id: int|null, sector: string|null, other: string|null}
     */
    private function fallbackFromTrip(TransportTrip $trip): array
    {
        if ($trip->destinationFacility) {
            $facility = $trip->destinationFacility;

            return [
                'district' => $facility->districtDivision?->name ?? $facility->getRawOriginal('district'),
                'district_id' => $facility->district_id ? (int) $facility->district_id : null,
                'sector' => $facility->sectorDivision?->name ?? $facility->getRawOriginal('sector'),
                'other' => $facility->facility_name,
            ];
        }

        return [
            'district' => null,
            'district_id' => null,
            'sector' => null,
            'other' => $trip->destination_name ?: $trip->destination_display,
        ];
    }

    /**
     * @param  array<int|string, float|int|string>  $releasedKgByCertificate
     * @param  array<int|string, float|int|string>  $releasedKgByBatch
     */
    private function resolveCertificateQtyKg(
        Certificate $certificate,
        array $releasedKgByCertificate,
        array $releasedKgByBatch = [],
    ): float {
        $carcassKg = data_get($certificate->pdf_details, 'carcass_meat_kg');
        $otherKg = data_get($certificate->pdf_details, 'other_meat_kg');
        $pdfTotal = 0.0;

        if (is_numeric($carcassKg)) {
            $pdfTotal += (float) $carcassKg;
        }

        if (is_numeric($otherKg)) {
            $pdfTotal += (float) $otherKg;
        }

        if ($pdfTotal > 0) {
            return round($pdfTotal, 2);
        }

        if (isset($releasedKgByCertificate[$certificate->id])) {
            return round((float) $releasedKgByCertificate[$certificate->id], 2);
        }

        if ($certificate->batch_id && isset($releasedKgByBatch[$certificate->batch_id])) {
            return round((float) $releasedKgByBatch[$certificate->batch_id], 2);
        }

        return round((float) ($certificate->batch?->quantity ?? 0), 2);
    }

    private function nullablePdfDetail(Certificate $certificate, string $key): ?string
    {
        $value = trim((string) data_get($certificate->pdf_details, $key));

        return $value !== '' && $value !== '—' ? $value : null;
    }

    /**
     * @return array{district: string|null, district_id: int|null, sector: string|null, other: string|null}
     */
    private function parseLocationLine(?string $line): array
    {
        $line = trim((string) $line);
        if ($line === '' || $line === '—') {
            return [
                'district' => null,
                'district_id' => null,
                'sector' => null,
                'other' => null,
            ];
        }

        $parts = array_values(array_filter(array_map('trim', preg_split('/[,|]/', $line) ?: [])));
        if ($parts === []) {
            return [
                'district' => null,
                'district_id' => null,
                'sector' => null,
                'other' => $line,
            ];
        }

        return [
            'district' => $parts[0] ?? null,
            'district_id' => null,
            'sector' => $parts[1] ?? null,
            'other' => $parts[2] ?? ($parts[1] ?? null),
        ];
    }
}
