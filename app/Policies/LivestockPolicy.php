<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\Livestock;
use App\Models\User;

class LivestockPolicy
{
    public function viewAny(User $user, Farm $farm): bool
    {
        return $user->accessibleFarmerBusinessIds()->contains((int) $farm->business_id);
    }

    public function create(User $user, Farm $farm): bool
    {
        return $this->viewAny($user, $farm);
    }

    public function view(User $user, Livestock $livestock): bool
    {
        return $user->accessibleFarmerBusinessIds()->contains((int) $livestock->farm?->business_id);
    }

    public function update(User $user, Livestock $livestock): bool
    {
        return $this->view($user, $livestock);
    }

    public function delete(User $user, Livestock $livestock): bool
    {
        return $this->view($user, $livestock);
    }
}
