<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\LogisticsCompany;
use App\Models\User;

class LogisticsCompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return Business::query()
            ->where('type', Business::TYPE_LOGISTICS)
            ->whereIn('id', $user->accessibleBusinessIds())
            ->exists();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, LogisticsCompany $company): bool
    {
        return in_array((int) $company->business_id, $user->accessibleBusinessIds()->all(), true);
    }

    public function update(User $user, LogisticsCompany $company): bool
    {
        return $this->view($user, $company);
    }

    public function delete(User $user, LogisticsCompany $company): bool
    {
        return $this->view($user, $company);
    }
}
