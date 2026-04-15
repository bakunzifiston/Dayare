<?php

namespace App\Services\Logistics;

use App\Models\Business;
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
        $client = Business::query()->find((int) $attributes['client_id']);
        if ($client === null || ! in_array($client->type, [Business::TYPE_FARMER, Business::TYPE_PROCESSOR], true)) {
            $this->ruleViolation(__('Order client must be farmer or processor.'), 'client_id');
        }
        if ((int) $attributes['quantity'] <= 0) {
            $this->ruleViolation(__('Quantity must be greater than zero.'), 'quantity');
        }

        $attributes['company_id'] = (int) $company->id;
        $attributes['status'] = $attributes['status'] ?? LogisticsOrder::STATUS_PENDING;

        return $this->orders->create($attributes);
    }

    public function approve(User $user, LogisticsOrder $order): LogisticsOrder
    {
        $this->companies->requireAccessible($user, (int) $order->company_id);
        if ($order->status !== LogisticsOrder::STATUS_PENDING) {
            $this->ruleViolation(__('Only pending orders can be approved.'), 'status');
        }

        $order->status = LogisticsOrder::STATUS_APPROVED;
        $this->orders->save($order);

        return $order->refresh();
    }
}

