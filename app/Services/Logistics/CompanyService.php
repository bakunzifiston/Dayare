<?php

namespace App\Services\Logistics;

use App\Models\Business;
use App\Models\LogisticsCompany;
use App\Models\User;
use App\Repositories\Logistics\CompanyRepository;
use App\Services\Logistics\Concerns\ThrowsRuleViolation;
use Illuminate\Support\Collection;

class CompanyService
{
    use ThrowsRuleViolation;

    public function __construct(private CompanyRepository $companies) {}

    public function create(User $user, array $attributes): LogisticsCompany
    {
        $businessId = (int) $attributes['business_id'];
        $allowedBusiness = Business::query()
            ->where('type', Business::TYPE_LOGISTICS)
            ->whereIn('id', $user->accessibleBusinessIds())
            ->whereKey($businessId)
            ->exists();

        if (! $allowedBusiness) {
            $this->ruleViolation(__('Company must belong to your logistics tenant.'), 'business_id');
        }

        return $this->companies->create($attributes);
    }

    /** @return Collection<int, LogisticsCompany> */
    public function list(User $user): Collection
    {
        return $this->companies->listForUser($user);
    }

    public function requireAccessible(User $user, int $companyId): LogisticsCompany
    {
        $company = $this->companies->findForUser($companyId, $user);
        if ($company === null) {
            $this->ruleViolation(__('Company not accessible.'), 'company_id');
        }

        return $company;
    }
}

