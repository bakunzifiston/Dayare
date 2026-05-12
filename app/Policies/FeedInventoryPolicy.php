<?php

namespace App\Policies;

use App\Models\FeedInventory;
use App\Models\User;
use App\Policies\Concerns\AuthorizesFarmerBusinessResource;

class FeedInventoryPolicy
{
    use AuthorizesFarmerBusinessResource;

    public function view(User $user, FeedInventory $feedInventory): bool
    {
        return $this->inFarmerBusiness($user, (int) $feedInventory->feedType?->business_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FeedInventory $feedInventory): bool
    {
        return $this->view($user, $feedInventory);
    }

    public function delete(User $user, FeedInventory $feedInventory): bool
    {
        return $this->view($user, $feedInventory);
    }
}
