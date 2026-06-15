<?php

namespace App\Services\Processor;

use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class ProcessorDashboardContext
{
    /** @param  Collection<int, int|string>  $facilityIds */
    public function __construct(
        public readonly int $businessId,
        public readonly Collection $facilityIds,
        public readonly Collection $planIds,
        public readonly Collection $executionIds,
        public readonly Collection $batchIds,
        public readonly Collection $certificateIds,
        public readonly Collection $tripIds,
        public readonly CarbonInterface $today,
    ) {}

    public static function forBusiness(int $businessId): self
    {
        $facilityIds = Facility::query()->where('business_id', $businessId)->pluck('id');
        $planIds = SlaughterPlan::query()->whereIn('facility_id', $facilityIds)->pluck('id');
        $executionIds = SlaughterExecution::query()->whereIn('slaughter_plan_id', $planIds)->pluck('id');
        $batchIds = Batch::query()->whereIn('slaughter_execution_id', $executionIds)->pluck('id');
        $certificateIds = Certificate::query()->whereIn('batch_id', $batchIds)->pluck('id');
        $tripIds = TransportTrip::query()->whereIn('certificate_id', $certificateIds)->pluck('id');

        return new self(
            $businessId,
            $facilityIds,
            $planIds,
            $executionIds,
            $batchIds,
            $certificateIds,
            $tripIds,
            now()->startOfDay(),
        );
    }
}
