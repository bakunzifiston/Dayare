<?php

namespace App\Services\Logistics;

use App\Models\LogisticsOrder;
use App\Models\User;
use App\Repositories\Logistics\OrderRepository;
use App\Services\Logistics\Concerns\ThrowsRuleViolation;

class OrderService
{
    use ThrowsRuleViolation;

    public function __construct(
        private CompanyService $companies,
        private OrderRepository $orders
    ) {}

    public function create(User $user, array $attributes): LogisticsOrder
    {
        $company = $this->companies->requireAccessible($user, (int) $attributes['company_id']);

        $attributes['company_id'] = (int) $company->id;
        $attributes['status'] = $attributes['status'] ?? LogisticsOrder::STATUS_CONFIRMED;

        return $this->orders->create($attributes);
    }
}
