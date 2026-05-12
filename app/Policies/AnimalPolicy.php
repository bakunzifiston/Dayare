<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\Livestock;
use App\Models\User;

class AnimalPolicy
{
    public function viewAny(User $user, Livestock $livestock): bool
    {
        return $user->accessibleFarmerBusinessIds()->contains((int) $livestock->farm?->business_id);
    }

    public function create(User $user, Livestock $livestock): bool
    {
        return $this->viewAny($user, $livestock);
    }

    public function view(User $user, Animal $animal): bool
    {
        return $user->accessibleFarmerBusinessIds()->contains((int) $animal->livestock?->farm?->business_id);
    }

    public function update(User $user, Animal $animal): bool
    {
        return $this->view($user, $animal);
    }

    public function delete(User $user, Animal $animal): bool
    {
        return $this->view($user, $animal);
    }
}
