<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AuthorizesFarmerBusinessResource
{
    protected function inFarmerBusiness(User $user, ?int $businessId): bool
    {
        return $businessId !== null && $user->accessibleFarmerBusinessIds()->contains($businessId);
    }

    public function viewAny(User $user): bool
    {
        return $user->accessibleFarmerBusinessIds()->isNotEmpty();
    }
}
