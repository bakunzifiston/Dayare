<?php

namespace App\Policies;

use App\Models\MovementPermit;
use App\Models\User;

class MovementPermitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->accessibleFarmerBusinessIds()->isNotEmpty();
    }

    public function view(User $user, MovementPermit $permit): bool
    {
        return $user->accessibleFarmerBusinessIds()->contains((int) $permit->farmer_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, MovementPermit $permit): bool
    {
        return $this->view($user, $permit);
    }

    public function delete(User $user, MovementPermit $permit): bool
    {
        return $this->view($user, $permit);
    }

    public function approve(User $user, MovementPermit $permit): bool
    {
        return $this->view($user, $permit);
    }
}
