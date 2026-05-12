<?php

namespace App\Policies;

use App\Models\FeedType;
use App\Models\User;
use App\Policies\Concerns\AuthorizesFarmerBusinessResource;

class FeedTypePolicy
{
    use AuthorizesFarmerBusinessResource;

    public function view(User $user, FeedType $feedType): bool
    {
        return $this->inFarmerBusiness($user, (int) $feedType->business_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FeedType $feedType): bool
    {
        return $this->view($user, $feedType);
    }

    public function delete(User $user, FeedType $feedType): bool
    {
        return $this->view($user, $feedType);
    }
}
