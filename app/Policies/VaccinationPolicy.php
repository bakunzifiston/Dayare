<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vaccination;
use App\Policies\Concerns\AuthorizesAnimalLinkedRecord;

class VaccinationPolicy
{
    use AuthorizesAnimalLinkedRecord;

    public function view(User $user, Vaccination $vaccination): bool
    {
        return $this->canAccessAnimal($user, $vaccination->animal);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Vaccination $vaccination): bool
    {
        return $this->view($user, $vaccination);
    }

    public function delete(User $user, Vaccination $vaccination): bool
    {
        return $this->view($user, $vaccination);
    }
}
