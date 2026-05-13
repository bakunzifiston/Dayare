<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalCertificate;

final class PublicAnimalIdentifierResolver
{
    /**
     * Resolve an animal (and optional certificate) from a public token, tag, code, or certificate number.
     *
     * @return array{certificate: ?AnimalCertificate, animal: Animal}|null
     */
    public function resolve(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $certificate = AnimalCertificate::query()
            ->where(function ($query) use ($token): void {
                $query->where('verification_token', $token)
                    ->orWhere('certificate_number', $token);
            })
            ->with(['animal.livestock.farm.business'])
            ->first();

        if ($certificate !== null) {
            $animal = $certificate->animal;
            if ($animal === null) {
                return null;
            }

            return ['certificate' => $certificate, 'animal' => $animal];
        }

        $animal = Animal::query()
            ->where('public_verification_token', $token)
            ->orWhere('animal_code', $token)
            ->orWhere('tag_number', $token)
            ->with(['livestock.farm.business'])
            ->first();

        if ($animal === null) {
            return null;
        }

        return ['certificate' => null, 'animal' => $animal];
    }
}
