<?php

namespace App\Services\Logistics;

use App\Models\Business;
use App\Models\LogisticsCompany;
use App\Models\User;
use App\Repositories\Logistics\CompanyRepository;
use App\Services\Logistics\Concerns\ThrowsRuleViolation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $members = $attributes['members'] ?? [];
        unset($attributes['members']);

        return DB::transaction(function () use ($attributes, $members) {
            $company = $this->companies->create($attributes);

            $this->syncMembers($company, $members);

            return $company;
        });
    }

    public function update(User $user, LogisticsCompany $company, array $attributes): LogisticsCompany
    {
        $this->requireAccessible($user, (int) $company->id);

        $businessId = (int) $attributes['business_id'];
        $allowedBusiness = Business::query()
            ->where('type', Business::TYPE_LOGISTICS)
            ->whereIn('id', $user->accessibleBusinessIds())
            ->whereKey($businessId)
            ->exists();

        if (! $allowedBusiness) {
            $this->ruleViolation(__('Company must belong to your logistics tenant.'), 'business_id');
        }

        $members = $attributes['members'] ?? [];
        unset($attributes['members']);

        return DB::transaction(function () use ($company, $attributes, $members) {
            $company->update($attributes);
            $company->refresh();

            $company->members()->delete();
            $this->syncMembers($company, $members);

            return $company->fresh(['members']);
        });
    }

    public function delete(User $user, LogisticsCompany $company): void
    {
        $this->requireAccessible($user, (int) $company->id);
        $company->delete();
    }

    /**
     * @param  array<int, mixed>  $members
     */
    private function syncMembers(LogisticsCompany $company, array $members): void
    {
        if (! $company->isSharedCompany() || ! is_array($members)) {
            return;
        }

        foreach ($members as $row) {
            if (! is_array($row)) {
                continue;
            }
            $company->members()->create([
                'first_name' => trim((string) ($row['first_name'] ?? '')),
                'last_name' => trim((string) ($row['last_name'] ?? '')),
                'phone' => trim((string) ($row['phone'] ?? '')),
                'email' => Str::lower(trim((string) ($row['email'] ?? ''))),
            ]);
        }
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
