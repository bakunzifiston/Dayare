<?php

namespace App\Policies;

use App\Models\AnimalCertificate;
use App\Models\User;

class AnimalCertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->accessibleFarmerBusinessIds()->isNotEmpty();
    }

    public function view(User $user, AnimalCertificate $certificate): bool
    {
        return $user->accessibleFarmerBusinessIds()->contains((int) $certificate->animal?->livestock?->farm?->business_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, AnimalCertificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }

    public function delete(User $user, AnimalCertificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }
}
