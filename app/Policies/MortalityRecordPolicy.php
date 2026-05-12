<?php

namespace App\Policies;

use App\Models\MortalityRecord;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAnimalLinkedRecord;

class MortalityRecordPolicy
{
    use AuthorizesAnimalLinkedRecord;

    public function view(User $user, MortalityRecord $mortalityRecord): bool
    {
        return $this->canAccessAnimal($user, $mortalityRecord->animal);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, MortalityRecord $mortalityRecord): bool
    {
        return $this->view($user, $mortalityRecord);
    }

    public function delete(User $user, MortalityRecord $mortalityRecord): bool
    {
        return $this->view($user, $mortalityRecord);
    }
}
