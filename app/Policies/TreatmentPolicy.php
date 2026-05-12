<?php

namespace App\Policies;

use App\Models\Treatment;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAnimalLinkedRecord;

class TreatmentPolicy
{
    use AuthorizesAnimalLinkedRecord;

    public function view(User $user, Treatment $treatment): bool
    {
        return $this->canAccessAnimal($user, $treatment->animal);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Treatment $treatment): bool
    {
        return $this->view($user, $treatment);
    }

    public function delete(User $user, Treatment $treatment): bool
    {
        return $this->view($user, $treatment);
    }
}
