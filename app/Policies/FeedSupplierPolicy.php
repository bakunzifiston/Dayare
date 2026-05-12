<?php

namespace App\Policies;

use App\Models\FeedSupplier;
use App\Models\User;
use App\Policies\Concerns\AuthorizesFarmerBusinessResource;

class FeedSupplierPolicy
{
    use AuthorizesFarmerBusinessResource;

    public function view(User $user, FeedSupplier $feedSupplier): bool
    {
        return $this->inFarmerBusiness($user, (int) $feedSupplier->business_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FeedSupplier $feedSupplier): bool
    {
        return $this->view($user, $feedSupplier);
    }

    public function delete(User $user, FeedSupplier $feedSupplier): bool
    {
        return $this->view($user, $feedSupplier);
    }
}
