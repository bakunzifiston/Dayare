<?php

namespace App\Policies;

use App\Models\LogisticsCompany;
use App\Models\LogisticsOrder;
use App\Models\User;

class LogisticsOrderPolicy
{
    public function update(User $user, LogisticsOrder $order): bool
    {
        return LogisticsCompany::query()
            ->whereKey($order->company_id)
            ->whereIn('business_id', $user->accessibleBusinessIds())
            ->exists();
    }
}

