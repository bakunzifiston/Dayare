<?php

namespace App\Policies\Concerns;

use App\Models\Animal;
use App\Models\User;

trait AuthorizesAnimalLinkedRecord
{
    protected function canAccessAnimal(User $user, Animal $animal): bool
    {
        return $user->accessibleFarmerBusinessIds()->contains((int) $animal->livestock?->farm?->business_id);
    }

    public function viewAny(User $user): bool
    {
        return $user->accessibleFarmerBusinessIds()->isNotEmpty();
    }
}
