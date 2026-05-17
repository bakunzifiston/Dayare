<?php

namespace App\Policies;

use App\Models\PermitRequest;
use App\Models\User;

class PermitRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->accessibleFarmerBusinessIds()->isNotEmpty();
    }

    public function view(User $user, PermitRequest $request): bool
    {
        return $user->accessibleFarmerBusinessIds()->contains((int) $request->farmer_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, PermitRequest $request): bool
    {
        return $this->view($user, $request);
    }

    public function delete(User $user, PermitRequest $request): bool
    {
        return $this->view($user, $request) && $request->isEditable();
    }

    public function review(User $user, PermitRequest $request): bool
    {
        return $this->view($user, $request);
    }
}
