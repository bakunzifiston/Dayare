<?php

namespace App\Services\Processor;

use App\Exceptions\CertificatePdfException;
use App\Models\AnimalIntake;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Models\Client;
use App\Models\ColdRoomTemperatureLog;
use App\Models\Facility;
use App\Models\TemperatureLog;
use App\Models\TransportTrip;
use App\Models\WarehouseStorage;
use App\Support\DomPdf;
use App\Support\PdfQrCode;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Collection;

class CertificatePdfService
{
    public const NYAGATARE_FACILITY_NAME = 'NYAGATARE MODERN SLAUGHTER HOUSE';

    /**
     * @throws CertificatePdfException
     */
    public function validate(Certificate $certificate): void
    {
        $certificate->loadMissing(['facility.business', 'inspector', 'batch']);

        if ($certificate->batch_id === null) {
            throw new CertificatePdfException(__('A batch is required to generate this certificate.'));
        }

        $facility = $certificate->facility;
        if ($facility === null) {
            throw new CertificatePdfException(__('A slaughterhouse facility is required to generate this certificate.'));
        }

        if ($facility->facility_type !== Facility::TYPE_SLAUGHTERHOUSE) {
            throw new CertificatePdfException(__('This certificate template is only valid for slaughterhouse facilities.'));
        }

        if (trim($this->resolvedSlaughterhouseDisplayName($certificate)) === '') {
            throw new CertificatePdfException(__('Enter the slaughterhouse name on the certificate before generating the PDF.'));
        }

        if (! $this->facilityLocationIsComplete($facility)) {
            throw new CertificatePdfException(__('Slaughterhouse location (District, Sector, Cell) must be complete before issuing a certificate.'));
        }

        if ((int) $certificate->inspector?->facility_id !== (int) $certificate->facility_id) {
            throw new CertificatePdfException(__('The inspector must be assigned to this slaughterhouse facility.'));
        }

        if (! $this->hasReleasedStorageForBatch((int) $certificate->batch_id)) {
            throw new CertificatePdfException(__('At least one released cold room storage record is required for this batch before generating the certificate.'));
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CertificatePdfException
     */
    public function buildViewData(Certificate $certificate): array
    {
        $this->validate($certificate);

        $certificate = $this->loadCertificateRelations($certificate);
        $facility = $certificate->facility;
        $batch = $certificate->batch;
        $releasedStorages = $this->releasedStoragesForBatch((int) $batch->id);
        $owner = $this->resolveBatchOwner($batch);
        $transportTrip = $this->resolveTransportTrip($certificate);
        $qr = $certificate->certificateQr ?? $certificate->certificateQr()->create([
            'slug' => CertificateQr::generateSlug(),
        ]);
        $issuedAt = $certificate->issued_at;
        $auto = $this->autoPdfDetails($batch, $facility, $releasedStorages, $owner, $transportTrip);

        return [
            'certificate' => $certificate,
            'facility' => $facility,
            'batch' => $batch,
            'owner' => $owner,
            'releasedEarTags' => $this->releasedEarTags($releasedStorages, $batch),
            'ownerNames' => $this->pdfField($certificate, 'animal_names', $auto['animal_names']),
            'butcherName' => $this->pdfField($certificate, 'butcher_name', $auto['butcher_name']),
            'ownerPhone' => $this->pdfField($certificate, 'owner_phone', $auto['owner_phone']),
            'shopName' => $this->pdfField($certificate, 'shop_name', $auto['shop_name']),
            'shopPhone' => $this->pdfField($certificate, 'shop_phone', $auto['shop_phone']),
            'carcassMeatKg' => (float) $this->pdfField($certificate, 'carcass_meat_kg', $auto['carcass_meat_kg']),
            'otherMeatKg' => (float) $this->pdfField($certificate, 'other_meat_kg', $auto['other_meat_kg']),
            'temperatureCelsius' => $this->pdfField($certificate, 'temperature_celsius', $auto['temperature_celsius']),
            'transportTrip' => $transportTrip,
            'transporterLicenseHolder' => $this->pdfField($certificate, 'transporter_license_holder', $auto['transporter_license_holder']),
            'vehiclePlateNumber' => $this->pdfField($certificate, 'vehicle_plate_number', $auto['vehicle_plate_number']),
            'driverName' => $this->pdfField($certificate, 'driver_name', $auto['driver_name']),
            'departureDestination' => $this->pdfField($certificate, 'departure_destination', $auto['departure_destination']),
            'transporterPhone' => $this->pdfField($certificate, 'transporter_phone', $auto['transporter_phone']),
            'slaughterhouseDisplayName' => $this->resolvedSlaughterhouseDisplayName($certificate),
            'headerDistrictLine' => $this->formatDivisionLine($this->facilityDistrict($facility), 'DISTRICT'),
            'headerSectorLine' => $this->formatDivisionLine($this->facilitySector($facility), 'SECTOR'),
            'headerCellLine' => $this->formatDivisionLine($this->facilityCell($facility), 'CELL'),
            'facilityLocationLine' => $this->pdfField($certificate, 'facility_location', $auto['facility_location']),
            'sellingLocationLine' => $this->pdfField($certificate, 'selling_location', $auto['selling_location']),
            'facilityTypeLabel' => $this->pdfField($certificate, 'facility_type', $auto['facility_type']),
            'facilityPhone' => $this->pdfField($certificate, 'facility_phone', $auto['facility_phone']),
            'facilityRegistrationNumber' => $this->pdfField($certificate, 'facility_registration', $auto['facility_registration']),
            'issuedDay' => $issuedAt?->format('d') ?: '..........',
            'issuedMonth' => $issuedAt?->format('m') ?: '..........',
            'issuedYear' => $issuedAt ? $issuedAt->format('Y') : '20..........',
            'qrImage' => PdfQrCode::dataUri($qr->trace_url),
            'generatedAt' => now(),
        ];
    }

    /**
     * Suggested values for the certificate PDF form (pre-filled, editable before issue).
     *
     * @return array<string, mixed>
     */
    public function suggestedPdfDetails(Batch $batch, ?Facility $facility = null, ?TransportTrip $transportTrip = null): array
    {
        $batch->loadMissing([
            'items.intakeItem',
            'slaughterExecution.slaughterPlan.animalIntake.client.districtDivision',
            'slaughterExecution.slaughterPlan.animalIntake.client.sectorDivision',
            'slaughterExecution.slaughterPlan.animalIntake.client.cell',
            'slaughterExecution.slaughterPlan.animalIntake.district',
            'slaughterExecution.slaughterPlan.animalIntake.sector',
            'slaughterExecution.slaughterPlan.animalIntake.cell',
        ]);

        $facility?->loadMissing(['districtDivision', 'sectorDivision', 'cell', 'business']);
        $facility ??= $batch->slaughterExecution?->slaughterPlan?->facility;
        $releasedStorages = $this->releasedStoragesForBatch((int) $batch->id);
        $owner = $this->resolveBatchOwner($batch);

        return $this->autoPdfDetails($batch, $facility, $releasedStorages, $owner, $transportTrip);
    }

    /**
     * @throws CertificatePdfException
     */
    public function generate(Certificate $certificate): PDF
    {
        $viewData = $this->buildViewData($certificate);

        return DomPdf::loadView('certificates.pdf.nyagatare', $viewData)
            ->setPaper('a4', 'portrait');
    }

    public function downloadFilename(Certificate $certificate): string
    {
        $certificate->loadMissing('batch');
        $batchRef = $certificate->batch?->batch_code ?: ('batch-'.$certificate->batch_id);
        $date = optional($certificate->issued_at)->format('Y-m-d') ?: now()->format('Y-m-d');

        return 'certificate_'.$batchRef.'_'.$date.'.pdf';
    }

    private function loadCertificateRelations(Certificate $certificate): Certificate
    {
        return $certificate->load([
            'facility.business',
            'facility.districtDivision',
            'facility.sectorDivision',
            'facility.cell',
            'inspector',
            'batch.items.intakeItem',
            'batch.slaughterExecution.slaughterPlan.animalIntake.client.districtDivision',
            'batch.slaughterExecution.slaughterPlan.animalIntake.client.sectorDivision',
            'batch.slaughterExecution.slaughterPlan.animalIntake.client.cell',
            'batch.slaughterExecution.slaughterPlan.animalIntake.district',
            'batch.slaughterExecution.slaughterPlan.animalIntake.sector',
            'batch.slaughterExecution.slaughterPlan.animalIntake.cell',
            'certificateQr',
            'transportTrips.destinationFacility',
            'transportTrips.originFacility',
        ]);
    }

    private function facilityLocationIsComplete(Facility $facility): bool
    {
        return $this->facilityDistrict($facility) !== null
            && $this->facilitySector($facility) !== null
            && $this->facilityCell($facility) !== null;
    }

    private function facilityDistrict(Facility $facility): ?string
    {
        $name = $facility->districtDivision?->name ?? $facility->getRawOriginal('district');

        return $this->nonEmptyString($name);
    }

    private function facilitySector(Facility $facility): ?string
    {
        $name = $facility->sectorDivision?->name ?? $facility->getRawOriginal('sector');

        return $this->nonEmptyString($name);
    }

    private function facilityCell(Facility $facility): ?string
    {
        return $this->nonEmptyString($facility->cell?->name);
    }

    private function nonEmptyString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function resolvedSlaughterhouseDisplayName(Certificate $certificate): string
    {
        $manualName = $this->nonEmptyString($certificate->slaughterhouse_display_name);
        if ($manualName !== null) {
            return $manualName;
        }

        return self::NYAGATARE_FACILITY_NAME;
    }

    private function pdfField(Certificate $certificate, string $key, mixed $autoValue): mixed
    {
        $manual = data_get($certificate->pdf_details, $key);

        if ($manual === null || $manual === '') {
            return $autoValue;
        }

        return $manual;
    }

    /**
     * @param  Collection<int, WarehouseStorage>  $releasedStorages
     * @param  object{
     *     name: string|null,
     *     business_name: string|null,
     *     phone: string|null,
     *     district: string|null,
     *     sector: string|null,
     *     cell: string|null
     * }  $owner
     * @return array<string, mixed>
     */
    private function autoPdfDetails(
        Batch $batch,
        ?Facility $facility,
        Collection $releasedStorages,
        object $owner,
        ?TransportTrip $transportTrip,
    ): array {
        $temperature = $this->resolveTemperature($releasedStorages);

        return [
            'facility_location' => $facility
                ? $this->formatLocationLine(
                    $this->facilityDistrict($facility),
                    $this->facilitySector($facility),
                    $this->facilityCell($facility),
                )
                : '—',
            'facility_type' => $facility ? $this->facilityTypeLabel($facility) : '—',
            'facility_phone' => $facility?->phone ?: '',
            'facility_registration' => $facility?->registration_number ?: '',
            'animal_names' => $this->resolveOwnerNames($releasedStorages, $batch, $owner),
            'butcher_name' => $owner->name ?: '',
            'selling_location' => $this->formatLocationLine($owner->district, $owner->sector, $owner->cell),
            'owner_phone' => $owner->phone ?: '',
            'shop_name' => $owner->business_name ?: $owner->name ?: '',
            'shop_phone' => $owner->phone ?: '',
            'carcass_meat_kg' => $this->sumReleasedQuantity($releasedStorages),
            'other_meat_kg' => 0,
            'temperature_celsius' => $temperature,
            'transporter_license_holder' => $transportTrip?->driver_name ?: '',
            'vehicle_plate_number' => $transportTrip?->vehicle_plate_number ?: '',
            'driver_name' => $transportTrip?->driver_name ?: '',
            'departure_destination' => $transportTrip?->destination_display ?: '',
            'transporter_phone' => $transportTrip?->driver_phone ?: '',
        ];
    }

    private function hasReleasedStorageForBatch(int $batchId): bool
    {
        return WarehouseStorage::query()
            ->where('batch_id', $batchId)
            ->where('status', WarehouseStorage::STATUS_RELEASED)
            ->exists();
    }

    /**
     * @return Collection<int, WarehouseStorage>
     */
    private function releasedStoragesForBatch(int $batchId): Collection
    {
        return WarehouseStorage::query()
            ->where('batch_id', $batchId)
            ->where('status', WarehouseStorage::STATUS_RELEASED)
            ->with(['intakeItem', 'coldRoom'])
            ->get();
    }

    /**
     * @param  Collection<int, WarehouseStorage>  $releasedStorages
     */
    private function sumReleasedQuantity(Collection $releasedStorages): float
    {
        return (float) $releasedStorages->sum('quantity_stored');
    }

    /**
     * @param  Collection<int, WarehouseStorage>  $releasedStorages
     */
    private function resolveTemperature(Collection $releasedStorages): ?float
    {
        $coldRoomIds = $releasedStorages
            ->pluck('cold_room_id')
            ->filter()
            ->unique()
            ->values();

        if ($coldRoomIds->isNotEmpty()) {
            $coldRoomLog = ColdRoomTemperatureLog::query()
                ->whereIn('cold_room_id', $coldRoomIds)
                ->orderByDesc('recorded_at')
                ->first();

            if ($coldRoomLog !== null) {
                return (float) $coldRoomLog->temperature;
            }
        }

        $storageIds = $releasedStorages->pluck('id');
        if ($storageIds->isNotEmpty()) {
            $warehouseLog = TemperatureLog::query()
                ->whereIn('warehouse_storage_id', $storageIds)
                ->orderByDesc('recorded_at')
                ->first();

            if ($warehouseLog !== null) {
                return (float) $warehouseLog->recorded_temperature;
            }
        }

        $entryTemperature = $releasedStorages
            ->pluck('temperature_at_entry')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->first();

        return $entryTemperature !== null ? (float) $entryTemperature : null;
    }

    /**
     * @param  Collection<int, WarehouseStorage>  $releasedStorages
     * @return list<string>
     */
    private function releasedEarTags(Collection $releasedStorages, \App\Models\Batch $batch): array
    {
        $tags = $releasedStorages
            ->map(fn (WarehouseStorage $storage) => $storage->intakeItem?->ear_tag)
            ->filter(fn (?string $tag) => $tag !== null && trim($tag) !== '')
            ->unique()
            ->values();

        if ($tags->isNotEmpty()) {
            return $tags->all();
        }

        $releasedItemIds = $releasedStorages
            ->pluck('animal_intake_item_id')
            ->filter()
            ->unique();

        if ($releasedItemIds->isEmpty()) {
            return [];
        }

        return $batch->items
            ->filter(fn ($item) => $releasedItemIds->contains($item->animal_intake_item_id))
            ->map(fn ($item) => $item->intakeItem?->ear_tag)
            ->filter(fn (?string $tag) => $tag !== null && trim($tag) !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return object{
     *     name: string|null,
     *     business_name: string|null,
     *     phone: string|null,
     *     district: string|null,
     *     sector: string|null,
     *     cell: string|null
     * }
     */
    private function resolveBatchOwner(\App\Models\Batch $batch): object
    {
        $intake = $batch->slaughterExecution?->slaughterPlan?->animalIntake;

        if ($intake === null) {
            return (object) [
                'name' => null,
                'business_name' => null,
                'phone' => null,
                'district' => null,
                'sector' => null,
                'cell' => null,
            ];
        }

        $client = $intake->client;
        if ($client instanceof Client) {
            return (object) [
                'name' => $client->contact_person ?: $client->name,
                'business_name' => $client->name,
                'phone' => $client->phone,
                'district' => $client->districtDivision?->name,
                'sector' => $client->sectorDivision?->name,
                'cell' => $client->cell?->name,
            ];
        }

        $supplierName = trim((string) ($intake->supplier_firstname ?? '').' '.(string) ($intake->supplier_lastname ?? ''));

        return (object) [
            'name' => $supplierName !== '' ? $supplierName : null,
            'business_name' => $supplierName !== '' ? $supplierName : null,
            'phone' => $intake->supplier_contact,
            'district' => $intake->district?->name,
            'sector' => $intake->sector?->name,
            'cell' => $intake->cell?->name,
        ];
    }

    private function resolveTransportTrip(Certificate $certificate): ?TransportTrip
    {
        if ($certificate->relationLoaded('transportTrips') && $certificate->transportTrips->isNotEmpty()) {
            return $certificate->transportTrips->sortByDesc('departure_date')->first();
        }

        return $certificate->transportTrips()
            ->with(['destinationFacility', 'originFacility'])
            ->orderByDesc('departure_date')
            ->first();
    }

    private function formatDivisionLine(?string $name, string $suffix): string
    {
        if ($name === null) {
            return '—';
        }

        $upper = strtoupper(trim($name));
        if (str_ends_with($upper, ' '.$suffix)) {
            return $upper;
        }

        return $upper.' '.$suffix;
    }

    private function formatLocationLine(?string $district, ?string $sector, ?string $cell): string
    {
        $parts = array_filter([
            $district,
            $sector,
            $cell,
        ], fn (?string $part) => $part !== null && trim($part) !== '');

        return $parts !== [] ? implode(', ', $parts) : '—';
    }

    private function facilityTypeLabel(Facility $facility): string
    {
        return match ($facility->facility_type) {
            Facility::TYPE_SLAUGHTERHOUSE => __('Slaughterhouse'),
            Facility::TYPE_STORAGE => __('Cold storage'),
            default => ucfirst(str_replace('_', ' ', (string) $facility->facility_type)),
        };
    }

    /**
     * @param  Collection<int, WarehouseStorage>  $releasedStorages
     * @param  object{
     *     name: string|null,
     *     business_name: string|null,
     *     phone: string|null,
     *     district: string|null,
     *     sector: string|null,
     *     cell: string|null
     * }  $owner
     */
    private function resolveOwnerNames(Collection $releasedStorages, \App\Models\Batch $batch, object $owner): string
    {
        $earTags = $this->releasedEarTags($releasedStorages, $batch);
        if ($earTags !== []) {
            return implode(', ', $earTags);
        }

        return $owner->name ?: '—';
    }
}
