<?php

namespace App\Policies;

use App\Models\AnimalCertificateTemplate;
use App\Models\User;
use App\Policies\Concerns\AuthorizesFarmerBusinessResource;

class AnimalCertificateTemplatePolicy
{
    use AuthorizesFarmerBusinessResource;

    public function view(User $user, AnimalCertificateTemplate $template): bool
    {
        return $this->inFarmerBusiness($user, (int) $template->business_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, AnimalCertificateTemplate $template): bool
    {
        return $this->view($user, $template);
    }

    public function delete(User $user, AnimalCertificateTemplate $template): bool
    {
        return $this->view($user, $template);
    }
}
