<?php

namespace App\Policies;

use App\Models\FeedingSchedule;
use App\Models\User;
use App\Policies\Concerns\AuthorizesFarmerBusinessResource;

class FeedingSchedulePolicy
{
    use AuthorizesFarmerBusinessResource;

    public function view(User $user, FeedingSchedule $feedingSchedule): bool
    {
        return $this->inFarmerBusiness($user, (int) $feedingSchedule->business_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FeedingSchedule $feedingSchedule): bool
    {
        return $this->view($user, $feedingSchedule);
    }

    public function delete(User $user, FeedingSchedule $feedingSchedule): bool
    {
        return $this->view($user, $feedingSchedule);
    }
}
