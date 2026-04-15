<?php

namespace App\Policies;

use App\Models\LogisticsCompany;
use App\Models\LogisticsTrip;
use App\Models\User;

class LogisticsTripPolicy
{
    public function update(User $user, LogisticsTrip $trip): bool
    {
        return LogisticsCompany::query()
            ->whereKey($trip->company_id)
            ->whereIn('business_id', $user->accessibleBusinessIds())
            ->exists();
    }
}

