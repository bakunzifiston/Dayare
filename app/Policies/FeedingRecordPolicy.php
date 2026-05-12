<?php

namespace App\Policies;

use App\Models\FeedingRecord;
use App\Models\User;
use App\Policies\Concerns\AuthorizesFarmerBusinessResource;

class FeedingRecordPolicy
{
    use AuthorizesFarmerBusinessResource;

    public function view(User $user, FeedingRecord $feedingRecord): bool
    {
        return $this->inFarmerBusiness($user, (int) $feedingRecord->feedType?->business_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FeedingRecord $feedingRecord): bool
    {
        return $this->view($user, $feedingRecord);
    }

    public function delete(User $user, FeedingRecord $feedingRecord): bool
    {
        return $this->view($user, $feedingRecord);
    }
}
