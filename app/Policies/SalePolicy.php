<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->accessibleFarmerBusinessIds()->isNotEmpty();
    }

    public function view(User $user, Sale $sale): bool
    {
        return $this->ownsFarm($user, $sale);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Sale $sale): bool
    {
        return $this->ownsFarm($user, $sale);
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $this->ownsFarm($user, $sale);
    }

    public function approve(User $user, Sale $sale): bool
    {
        return $this->ownsFarm($user, $sale);
    }

    public function complete(User $user, Sale $sale): bool
    {
        return $this->ownsFarm($user, $sale);
    }

    private function ownsFarm(User $user, Sale $sale): bool
    {
        $sale->loadMissing('farm');

        return $user->accessibleFarmerBusinessIds()->contains((int) $sale->farm?->business_id);
    }
}
