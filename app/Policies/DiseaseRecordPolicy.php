<?php

namespace App\Policies;

use App\Models\DiseaseRecord;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAnimalLinkedRecord;

class DiseaseRecordPolicy
{
    use AuthorizesAnimalLinkedRecord;

    public function view(User $user, DiseaseRecord $diseaseRecord): bool
    {
        return $this->canAccessAnimal($user, $diseaseRecord->animal);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, DiseaseRecord $diseaseRecord): bool
    {
        return $this->view($user, $diseaseRecord);
    }

    public function delete(User $user, DiseaseRecord $diseaseRecord): bool
    {
        return $this->view($user, $diseaseRecord);
    }
}
