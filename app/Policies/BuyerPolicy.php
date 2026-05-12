<?php

namespace App\Policies;

use App\Models\Buyer;
use App\Models\User;
use App\Policies\Concerns\AuthorizesFarmerBusinessResource;

class BuyerPolicy
{
    use AuthorizesFarmerBusinessResource;

    public function view(User $user, Buyer $buyer): bool
    {
        return $this->inFarmerBusiness($user, (int) $buyer->business_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Buyer $buyer): bool
    {
        return $this->view($user, $buyer);
    }

    public function delete(User $user, Buyer $buyer): bool
    {
        return $this->view($user, $buyer);
    }
}
