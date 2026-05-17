<?php

namespace App\DataTransferObjects;

final class RwandaMovementPermitExtraction
{
    /**
     * @param  list<array{
     *     ear_tag: string,
     *     name: ?string,
     *     sex: ?string,
     *     quantity: int,
     *     breed: ?string,
     *     color_mark: ?string,
     *     description: ?string,
     *     species: ?string,
     * }>  $animals
     */
    public function __construct(
        public readonly string $permitNumber,
        public readonly ?string $ownerName,
        public readonly ?string $ownerNationalId,
        public readonly ?string $movementReason,
        public readonly ?string $transportMode,
        public readonly ?string $vehiclePlate,
        public readonly ?string $transportNotes,
        public readonly ?string $issueDate,
        public readonly ?string $expiryDate,
        public readonly ?string $originDistrict,
        public readonly ?string $originSector,
        public readonly ?string $originCell,
        public readonly ?string $originVillage,
        public readonly ?string $destinationDistrict,
        public readonly ?string $destinationSector,
        public readonly ?string $destinationCell,
        public readonly ?string $destinationVillage,
        public readonly ?string $issuingOfficer,
        public readonly ?string $species,
        public readonly array $animals,
        public readonly string $rawText,
    ) {}

    public function originLocation(): ?string
    {
        return $this->formatLocation(
            $this->originVillage,
            $this->originCell,
            $this->originSector,
            $this->originDistrict,
        );
    }

    public function destinationLocation(): ?string
    {
        return $this->formatLocation(
            $this->destinationVillage,
            $this->destinationCell,
            $this->destinationSector,
            $this->destinationDistrict,
        );
    }

    private function formatLocation(?string $village, ?string $cell, ?string $sector, ?string $district): ?string
    {
        $parts = array_values(array_filter([
            $village,
            $cell,
            $sector,
            $district,
        ], fn (?string $part) => filled($part)));

        return $parts === [] ? null : implode(', ', $parts);
    }
}
