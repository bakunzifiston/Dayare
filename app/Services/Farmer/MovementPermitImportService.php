<?php

namespace App\Services\Farmer;

use App\DataTransferObjects\RwandaMovementPermitExtraction;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\MovementLog;
use App\Models\MovementPermit;
use App\Models\MovementPermitAnimal;
use App\Models\MovementTransport;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovementPermitImportService
{
    public function __construct(
        private readonly RwandaMovementPermitPdfExtractor $extractor,
        private readonly MovementHistoryService $history,
    ) {}

    public function import(
        UploadedFile $pdf,
        int $sourceFarmId,
        int $userId,
        Collection $accessibleBusinessIds,
        ?string $ip = null,
    ): MovementPermit {
        $farm = Farm::query()->find($sourceFarmId);
        if (! $farm || ! $accessibleBusinessIds->contains((int) $farm->business_id)) {
            throw ValidationException::withMessages([
                'source_farm_id' => __('Selected farm is not accessible.'),
            ]);
        }

        $extraction = $this->extractor->extractFromFile($pdf);

        if (MovementPermit::withTrashed()->where('permit_number', $extraction->permitNumber)->exists()) {
            throw ValidationException::withMessages([
                'permit_pdf' => __('A permit with number :number already exists.', ['number' => $extraction->permitNumber]),
            ]);
        }

        $storedPdfPath = $pdf->storeAs(
            'movement-permits/originals',
            $this->safeFilename($extraction->permitNumber, $pdf),
            'public',
        );

        return DB::transaction(function () use ($extraction, $farm, $userId, $ip, $storedPdfPath, $accessibleBusinessIds): MovementPermit {
            $issueDate = $extraction->issueDate ?? now()->toDateString();
            $expiryDate = $extraction->expiryDate ?? $issueDate;
            $permitStatus = $this->resolvePermitStatus($expiryDate);

            $codes = app(MovementPermitCodeService::class);

            $permit = MovementPermit::query()->create([
                'permit_number' => $extraction->permitNumber,
                'permit_type' => MovementPermit::TYPE_FARM_TRANSFER,
                'movement_reason' => $extraction->movementReason,
                'livestock_type' => $extraction->species,
                'owner_name' => $extraction->ownerName,
                'owner_national_id' => $extraction->ownerNationalId,
                'owner_identification_number' => $extraction->ownerNationalId,
                'owner_address' => $extraction->originLocation(),
                'farmer_id' => $farm->business_id,
                'source_farm_id' => $farm->id,
                'origin_location' => $extraction->originLocation(),
                'source_district' => $extraction->originDistrict,
                'source_sector' => $extraction->originSector,
                'source_cell' => $extraction->originCell,
                'source_village' => $extraction->originVillage,
                'destination_location' => $extraction->destinationLocation(),
                'destination_district' => $extraction->destinationDistrict,
                'destination_sector' => $extraction->destinationSector,
                'destination_cell' => $extraction->destinationCell,
                'destination_village' => $extraction->destinationVillage,
                'departure_date' => $issueDate,
                'expected_arrival_date' => $expiryDate,
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate,
                'transport_mode' => $extraction->transportMode,
                'vehicle_plate' => $extraction->vehiclePlate,
                'transporter_name' => null,
                'issued_by' => $extraction->issuingOfficer ?? 'RAB',
                'issuing_authority' => 'RAB — Rwanda Agriculture and Animal Resources Development Board',
                'permit_status' => $permitStatus,
                'veterinary_status' => MovementPermit::VET_CLEARED,
                'movement_status' => $permitStatus === MovementPermit::STATUS_EXPIRED
                    ? MovementPermit::MOVEMENT_CANCELLED
                    : MovementPermit::MOVEMENT_PENDING,
                'verification_token' => $codes->generateVerificationToken(),
                'verification_code' => $codes->generateVerificationCode(),
                'notes' => $extraction->transportNotes,
                'file_path' => $storedPdfPath,
                'pdf_path' => $storedPdfPath,
                'attachment_path' => $storedPdfPath,
                'imported_from_pdf' => true,
                'created_by' => $userId,
            ]);

            $permit->update(['qr_code' => $permit->verificationUrl()]);

            $this->syncTransport($permit, $extraction);
            $this->syncAnimals($permit, $extraction, $accessibleBusinessIds);
            $this->history->log($permit, MovementLog::ACTION_CREATED, $userId, $ip, __('Imported from Rwanda movement permit PDF.'));

            if ($permitStatus === MovementPermit::STATUS_ISSUED || $permitStatus === MovementPermit::STATUS_ACTIVE) {
                app(AnimalMovementTraceService::class)->recordOnActivation($permit, $userId);
            }

            return $permit->fresh(['animals.animal', 'transport', 'sourceFarm']);
        });
    }

    public function resolvePermitStatus(string $expiryDate): string
    {
        $expiry = Carbon::parse($expiryDate)->startOfDay();

        if ($expiry->lt(now()->startOfDay())) {
            return MovementPermit::STATUS_EXPIRED;
        }

        return MovementPermit::STATUS_ISSUED;
    }

    private function syncTransport(MovementPermit $permit, RwandaMovementPermitExtraction $extraction): void
    {
        MovementTransport::query()->updateOrCreate(
            ['movement_permit_id' => $permit->id],
            [
                'vehicle_type' => $extraction->transportMode,
                'vehicle_number' => $extraction->vehiclePlate,
                'driver_name' => null,
                'driver_phone' => null,
                'transporter_company' => null,
                'route_information' => trim(implode(' → ', array_filter([
                    $extraction->originLocation(),
                    $extraction->destinationLocation(),
                ]))),
                'transport_notes' => $extraction->transportNotes,
            ],
        );
    }

    private function syncAnimals(
        MovementPermit $permit,
        RwandaMovementPermitExtraction $extraction,
        Collection $accessibleBusinessIds,
    ): void {
        foreach ($extraction->animals as $line) {
            $matchedAnimal = $this->matchAnimalByEarTag($line['ear_tag'], $accessibleBusinessIds);

            MovementPermitAnimal::query()->create([
                'movement_permit_id' => $permit->id,
                'animal_id' => $matchedAnimal?->id,
                'livestock_id' => $matchedAnimal?->livestock_id,
                'animal_identifier' => $line['ear_tag'],
                'species' => $line['species'] ?? $extraction->species,
                'breed' => $line['breed'],
                'sex' => $line['sex'],
                'age_description' => (string) $line['quantity'],
                'quantity' => $line['quantity'],
                'movement_condition' => MovementPermitAnimal::CONDITION_HEALTHY,
                'inspection_notes' => trim(implode(' · ', array_filter([
                    $line['color_mark'],
                    $line['description'],
                ]))),
                'notes' => $line['name'],
            ]);
        }
    }

    private function matchAnimalByEarTag(string $earTag, Collection $accessibleBusinessIds): ?Animal
    {
        $earTag = trim($earTag);
        if ($earTag === '') {
            return null;
        }

        return Animal::query()
            ->where(function ($query) use ($earTag): void {
                $query->where('tag_number', $earTag)
                    ->orWhere('animal_code', $earTag);
            })
            ->whereHas('livestock.farm', fn ($query) => $query->whereIn('business_id', $accessibleBusinessIds))
            ->first();
    }

    private function safeFilename(string $permitNumber, UploadedFile $pdf): string
    {
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $permitNumber) ?: 'permit';
        $extension = $pdf->getClientOriginalExtension() ?: 'pdf';

        return $base.'.'.strtolower($extension);
    }
}
