<?php

namespace App\Services\Farmer;

use App\Models\Animal;
use App\Models\Farm;
use App\Models\MovementLog;
use App\Models\MovementPermit;
use App\Models\MovementPermitAnimal;
use App\Models\MovementTransport;
use App\Models\MovementVeterinaryApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovementPermitService
{
    public function __construct(
        private readonly MovementPermitCodeService $codes,
        private readonly MovementHistoryService $history,
        private readonly MovementPermitPdfService $pdfService,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, int $userId, ?string $ip = null): MovementPermit
    {
        return DB::transaction(function () use ($data, $userId, $ip): MovementPermit {
            $farm = Farm::query()->findOrFail((int) $data['source_farm_id']);
            $lines = $data['lines'] ?? [];
            $transport = $data['transport'] ?? null;
            $veterinary = $data['veterinary'] ?? null;
            unset($data['lines'], $data['transport'], $data['veterinary']);

            $permit = MovementPermit::query()->create($this->permitAttributes($data, $farm, $userId));
            $this->syncLines($permit, $lines);
            $this->syncTransport($permit, $transport);
            $this->syncVeterinary($permit, $veterinary);
            $this->refreshQr($permit);
            $this->history->log($permit, MovementLog::ACTION_CREATED, $userId, $ip);

            return $permit->fresh(['animals.animal', 'transport', 'veterinaryApproval']);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(MovementPermit $permit, array $data, int $userId, ?string $ip = null): MovementPermit
    {
        if (! $permit->isEditable()) {
            throw ValidationException::withMessages(['permit_status' => __('This permit can no longer be edited.')]);
        }

        return DB::transaction(function () use ($permit, $data, $userId, $ip): MovementPermit {
            $farm = Farm::query()->findOrFail((int) $data['source_farm_id']);
            $lines = $data['lines'] ?? null;
            $transport = $data['transport'] ?? null;
            $veterinary = $data['veterinary'] ?? null;
            unset($data['lines'], $data['transport'], $data['veterinary']);

            $permit->update($this->permitAttributes($data, $farm, $userId, $permit));
            if (is_array($lines)) {
                $permit->animals()->delete();
                $this->syncLines($permit, $lines, $permit->id);
            }
            if (is_array($transport)) {
                $this->syncTransport($permit, $transport);
            }
            if (is_array($veterinary)) {
                $this->syncVeterinary($permit, $veterinary);
            }
            $this->refreshQr($permit);
            $this->history->log($permit, MovementLog::ACTION_UPDATED, $userId, $ip);

            return $permit->fresh(['animals.animal', 'transport', 'veterinaryApproval']);
        });
    }

    public function submitForApproval(MovementPermit $permit, int $userId, ?string $ip = null): MovementPermit
    {
        $this->assertHasAnimals($permit);
        $permit->update(['permit_status' => MovementPermit::STATUS_PENDING_APPROVAL]);
        $this->history->log($permit, MovementLog::ACTION_UPDATED, $userId, $ip, __('Submitted for approval.'));

        return $permit->fresh();
    }

    public function approve(MovementPermit $permit, int $userId, ?string $ip = null): MovementPermit
    {
        if ($permit->veterinary_status !== MovementPermit::VET_CLEARED) {
            throw ValidationException::withMessages(['veterinary_status' => __('Veterinary clearance is required before approval.')]);
        }
        if ($permit->expected_arrival_date && $permit->expected_arrival_date->isPast()) {
            throw ValidationException::withMessages(['expected_arrival_date' => __('Cannot approve an expired permit window.')]);
        }

        $permit->update([
            'permit_status' => MovementPermit::STATUS_APPROVED,
            'approved_by' => $userId,
        ]);
        $this->pdfService->generate($permit);
        $this->history->log($permit, MovementLog::ACTION_APPROVED, $userId, $ip);

        return $permit->fresh();
    }

    public function reject(MovementPermit $permit, int $userId, ?string $ip = null, ?string $notes = null): MovementPermit
    {
        $permit->update(['permit_status' => MovementPermit::STATUS_REJECTED]);
        $this->history->log($permit, MovementLog::ACTION_REJECTED, $userId, $ip, $notes);

        return $permit->fresh();
    }

    public function startTransit(MovementPermit $permit, int $userId, ?string $ip = null): MovementPermit
    {
        if ($permit->permit_status !== MovementPermit::STATUS_APPROVED) {
            throw ValidationException::withMessages(['permit_status' => __('Only approved permits can start transit.')]);
        }

        $permit->update(['movement_status' => MovementPermit::MOVEMENT_IN_TRANSIT]);
        $this->history->log($permit, MovementLog::ACTION_MOVEMENT_STARTED, $userId, $ip);

        return $permit->fresh();
    }

    public function confirmArrival(MovementPermit $permit, int $userId, ?string $ip = null): MovementPermit
    {
        $permit->update(['movement_status' => MovementPermit::MOVEMENT_ARRIVED]);
        $this->history->log($permit, MovementLog::ACTION_ARRIVED, $userId, $ip);

        return $permit->fresh();
    }

    public function cancel(MovementPermit $permit, int $userId, ?string $ip = null, ?string $notes = null): MovementPermit
    {
        $permit->update([
            'permit_status' => MovementPermit::STATUS_CANCELLED,
            'movement_status' => MovementPermit::MOVEMENT_CANCELLED,
        ]);
        $this->history->log($permit, MovementLog::ACTION_CANCELLED, $userId, $ip, $notes);

        return $permit->fresh();
    }

    /** @param  list<array<string, mixed>>  $lines */
    private function syncLines(MovementPermit $permit, array $lines, ?int $ignorePermitId = null): void
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => __('At least one animal must be assigned to the permit.')]);
        }

        foreach ($lines as $line) {
            $animalId = isset($line['animal_id']) ? (int) $line['animal_id'] : null;
            if ($animalId) {
                $this->assertAnimalAssignable($animalId, $ignorePermitId);
            }

            $animalIdentifier = trim((string) ($line['animal_identifier'] ?? ''));
            if ($animalIdentifier === '' && $animalId) {
                $animalIdentifier = Animal::query()->find($animalId)?->displayIdentifier() ?? '';
            }

            MovementPermitAnimal::query()->create([
                'movement_permit_id' => $permit->id,
                'animal_id' => $animalId,
                'livestock_id' => $line['livestock_id'] ?? null,
                'animal_identifier' => $animalIdentifier !== '' ? $animalIdentifier : null,
                'quantity' => $line['quantity'] ?? 1,
                'movement_condition' => $line['movement_condition'] ?? MovementPermitAnimal::CONDITION_HEALTHY,
                'inspection_notes' => $line['inspection_notes'] ?? null,
                'loading_status' => $line['loading_status'] ?? MovementPermitAnimal::LOADING_PENDING,
                'arrival_status' => $line['arrival_status'] ?? null,
                'notes' => $line['notes'] ?? null,
            ]);
        }
    }

    /** @param  array<string, mixed>|null  $transport */
    private function syncTransport(MovementPermit $permit, ?array $transport): void
    {
        if ($transport === null) {
            return;
        }

        MovementTransport::query()->updateOrCreate(
            ['movement_permit_id' => $permit->id],
            $transport,
        );
    }

    /** @param  array<string, mixed>|null  $veterinary */
    private function syncVeterinary(MovementPermit $permit, ?array $veterinary): void
    {
        if ($veterinary === null) {
            return;
        }

        $record = MovementVeterinaryApproval::query()->updateOrCreate(
            ['movement_permit_id' => $permit->id],
            $veterinary,
        );

        $vetStatus = $record->approval_status === MovementVeterinaryApproval::APPROVAL_APPROVED
            ? MovementPermit::VET_CLEARED
            : ($record->approval_status === MovementVeterinaryApproval::APPROVAL_REJECTED
                ? MovementPermit::VET_REJECTED
                : MovementPermit::VET_PENDING);

        $permit->update(['veterinary_status' => $vetStatus]);
    }

    private function assertAnimalAssignable(int $animalId, ?int $ignorePermitId = null): void
    {
        $animal = Animal::query()->find($animalId);
        if (! $animal || $animal->lifecycle_status === Animal::LIFECYCLE_DEAD) {
            throw ValidationException::withMessages(['lines' => __('One or more animals cannot be moved.')]);
        }

        $active = MovementPermitAnimal::query()
            ->where('animal_id', $animalId)
            ->whereHas('movementPermit', function ($query) use ($ignorePermitId): void {
                $query->whereIn('permit_status', MovementPermit::ACTIVE_STATUSES)
                    ->whereIn('movement_status', [MovementPermit::MOVEMENT_PENDING, MovementPermit::MOVEMENT_IN_TRANSIT]);
                if ($ignorePermitId) {
                    $query->where('id', '!=', $ignorePermitId);
                }
            })
            ->exists();

        if ($active) {
            throw ValidationException::withMessages(['lines' => __('One or more animals already belong to an active movement permit.')]);
        }
    }

    private function assertHasAnimals(MovementPermit $permit): void
    {
        if ($permit->animals()->count() === 0) {
            throw ValidationException::withMessages(['lines' => __('At least one animal must be assigned to the permit.')]);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function permitAttributes(array $data, Farm $farm, int $userId, ?MovementPermit $existing = null): array
    {
        $origin = $data['origin_location'] ?? $farm->name;
        $destination = $data['destination_location'] ?? null;
        if ($destination !== null && strcasecmp(trim($origin), trim($destination)) === 0) {
            throw ValidationException::withMessages(['destination_location' => __('Destination must differ from origin.')]);
        }

        $departure = $data['departure_date'] ?? $data['issue_date'] ?? now()->toDateString();
        $arrival = $data['expected_arrival_date'] ?? $data['expiry_date'] ?? $departure;

        return [
            'permit_number' => $existing?->permit_number ?? $this->codes->generate(),
            'permit_type' => $data['permit_type'] ?? MovementPermit::TYPE_FARM_TRANSFER,
            'movement_reason' => $data['movement_reason'] ?? null,
            'farmer_id' => $farm->business_id,
            'source_farm_id' => $farm->id,
            'origin_location' => $origin,
            'destination_location' => $destination,
            'destination_district_id' => $data['destination_district_id'] ?? null,
            'destination_sector_id' => $data['destination_sector_id'] ?? null,
            'destination_cell_id' => $data['destination_cell_id'] ?? null,
            'destination_village_id' => $data['destination_village_id'] ?? null,
            'departure_date' => $departure,
            'expected_arrival_date' => $arrival,
            'transport_mode' => $data['transport_mode'] ?? ($data['transport']['vehicle_type'] ?? null),
            'vehicle_plate' => $data['vehicle_plate'] ?? ($data['transport']['vehicle_number'] ?? null),
            'driver_name' => $data['driver_name'] ?? ($data['transport']['driver_name'] ?? null),
            'driver_phone' => $data['driver_phone'] ?? ($data['transport']['driver_phone'] ?? null),
            'transporter_name' => $data['transporter_name'] ?? ($data['transport']['transporter_company'] ?? null),
            'issue_date' => $departure,
            'expiry_date' => $arrival,
            'issued_by' => $data['issued_by'] ?? 'System',
            'permit_status' => $data['permit_status'] ?? MovementPermit::STATUS_DRAFT,
            'veterinary_status' => $data['veterinary_status'] ?? MovementPermit::VET_PENDING,
            'movement_status' => $data['movement_status'] ?? MovementPermit::MOVEMENT_PENDING,
            'verification_token' => $existing?->verification_token ?? $this->codes->generateVerificationToken(),
            'notes' => $data['notes'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? $existing?->attachment_path,
            'file_path' => $data['file_path'] ?? $existing?->file_path,
            'created_by' => $existing?->created_by ?? $userId,
        ];
    }

    private function refreshQr(MovementPermit $permit): void
    {
        $permit->update(['qr_code' => $permit->verificationUrl()]);
    }
}
