<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VeterinaryVisit;
use App\Policies\Concerns\AuthorizesAnimalLinkedRecord;

class VeterinaryVisitPolicy
{
    use AuthorizesAnimalLinkedRecord;

    public function view(User $user, VeterinaryVisit $veterinaryVisit): bool
    {
        return $this->canAccessAnimal($user, $veterinaryVisit->animal);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, VeterinaryVisit $veterinaryVisit): bool
    {
        return $this->view($user, $veterinaryVisit);
    }

    public function delete(User $user, VeterinaryVisit $veterinaryVisit): bool
    {
        return $this->view($user, $veterinaryVisit);
    }
}
