<?php

namespace App\Services\Logistics;

use App\Models\LogisticsComplianceDocument;
use App\Models\LogisticsTrip;
use App\Models\User;
use App\Repositories\Logistics\ComplianceRepository;

class ComplianceService
{
    public function __construct(
        private CompanyService $companies,
        private ComplianceRepository $compliance
    ) {}

    public function add(User $user, LogisticsTrip $trip, array $payload): LogisticsComplianceDocument
    {
        $this->companies->requireAccessible($user, (int) $trip->company_id);

        return $this->compliance->create([
            'trip_id' => (int) $trip->id,
            'type' => $payload['type'],
            'reference_id' => $payload['reference_id'] ?? null,
            'status' => $payload['status'],
        ]);
    }
}

