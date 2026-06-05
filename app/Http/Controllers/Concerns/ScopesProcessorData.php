<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ScopesProcessorData
{
    protected function accessibleFacilityIds(Request $request): Collection
    {
        return Facility::query()
            ->whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    protected function accessibleSlaughterPlanIds(Request $request): Collection
    {
        return SlaughterPlan::query()
            ->whereIn('facility_id', $this->accessibleFacilityIds($request))
            ->pluck('id');
    }

    protected function accessibleExecutionIds(Request $request): Collection
    {
        return SlaughterExecution::query()
            ->whereIn('slaughter_plan_id', $this->accessibleSlaughterPlanIds($request))
            ->pluck('id');
    }

    protected function accessibleBatchIds(Request $request): Collection
    {
        return Batch::query()
            ->whereIn('slaughter_execution_id', $this->accessibleExecutionIds($request))
            ->pluck('id');
    }

    protected function accessibleCertificateIds(Request $request): Collection
    {
        $batchIds = $this->accessibleBatchIds($request);
        $facilityIds = $this->accessibleFacilityIds($request);

        return Certificate::query()
            ->where(function ($q) use ($batchIds, $facilityIds) {
                $q->whereIn('batch_id', $batchIds)
                    ->orWhere(function ($q2) use ($facilityIds) {
                        $q2->whereNull('batch_id')->whereIn('facility_id', $facilityIds);
                    });
            })
            ->pluck('id');
    }

    protected function accessibleTripIds(Request $request): Collection
    {
        return TransportTrip::query()
            ->whereIn('certificate_id', $this->accessibleCertificateIds($request))
            ->pluck('id');
    }

    protected function accessibleClientIds(Request $request): Collection
    {
        return Client::query()
            ->whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    protected function scopedTripsQuery(Request $request): Builder
    {
        return TransportTrip::query()
            ->whereIn('certificate_id', $this->accessibleCertificateIds($request));
    }

    protected function scopedConfirmationsQuery(Request $request): Builder
    {
        return DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $this->accessibleTripIds($request));
    }

    protected function ensureTripInScope(Request $request, TransportTrip $trip): void
    {
        if (! $this->accessibleCertificateIds($request)->contains($trip->certificate_id)) {
            abort(404);
        }
    }

    protected function ensureConfirmationInScope(Request $request, DeliveryConfirmation $confirmation): void
    {
        if (! $this->accessibleTripIds($request)->contains($confirmation->transport_trip_id)) {
            abort(404);
        }
    }
}
