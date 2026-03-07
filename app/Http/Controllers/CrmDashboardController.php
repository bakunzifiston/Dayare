<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CrmDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $businessIds = $request->user()->businesses()->pluck('id');
        if ($businessIds->isEmpty()) {
            return view('crm.dashboard', [
                'totalClients' => 0,
                'openDemandsCount' => 0,
                'deliveriesThisMonth' => 0,
                'recentClients' => collect(),
                'openDemands' => collect(),
                'recipients' => collect(),
            ]);
        }

        $totalClients = Client::whereIn('business_id', $businessIds)->count();
        $openDemandsCount = Demand::whereIn('business_id', $businessIds)
            ->whereIn('status', [Demand::STATUS_DRAFT, Demand::STATUS_CONFIRMED, Demand::STATUS_IN_PROGRESS])
            ->count();

        $tripIds = $this->userTransportTripIds($request);
        $deliveriesThisMonth = DeliveryConfirmation::whereIn('transport_trip_id', $tripIds)
            ->whereMonth('received_date', now()->month)
            ->whereYear('received_date', now()->year)
            ->count();

        $recentClients = Client::with('business')
            ->whereIn('business_id', $businessIds)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        $openDemands = Demand::with(['client', 'destinationFacility'])
            ->whereIn('business_id', $businessIds)
            ->whereIn('status', [Demand::STATUS_DRAFT, Demand::STATUS_CONFIRMED, Demand::STATUS_IN_PROGRESS])
            ->orderBy('requested_delivery_date')
            ->limit(10)
            ->get();

        $recipients = $this->getRecipientsForDashboard($businessIds)->take(8);

        return view('crm.dashboard', compact(
            'totalClients',
            'openDemandsCount',
            'deliveriesThisMonth',
            'recentClients',
            'openDemands',
            'recipients'
        ));
    }

    /** Recipient facilities (last delivery date + count) for CRM dashboard. */
    private function getRecipientsForDashboard(Collection $businessIds): Collection
    {
        $facilityIds = Facility::whereIn('business_id', $businessIds)->pluck('id');
        $aggregates = DeliveryConfirmation::query()
            ->whereNotNull('receiving_facility_id')
            ->whereIn('receiving_facility_id', $facilityIds)
            ->selectRaw('receiving_facility_id as facility_id, max(received_date) as last_delivery_date, count(*) as delivery_count')
            ->groupBy('receiving_facility_id')
            ->orderByDesc('last_delivery_date')
            ->get();
        $facilities = Facility::with('business')
            ->whereIn('id', $aggregates->pluck('facility_id')->unique())
            ->get()
            ->keyBy('id');
        return $aggregates->map(fn ($row) => (object) [
            'facility_id' => $row->facility_id,
            'facility' => $facilities->get($row->facility_id),
            'last_delivery_date' => $row->last_delivery_date,
            'delivery_count' => (int) $row->delivery_count,
        ]);
    }

    private function userTransportTripIds(Request $request): \Illuminate\Support\Collection
    {
        $businessIds = $request->user()->businesses()->pluck('id');
        $facilityIds = Facility::whereIn('business_id', $businessIds)->pluck('id');
        $certificateIds = \App\Models\Certificate::where(function ($q) use ($facilityIds) {
            $q->whereIn('batch_id', \App\Models\Batch::whereIn('slaughter_execution_id',
                \App\Models\SlaughterExecution::whereIn('slaughter_plan_id',
                    \App\Models\SlaughterPlan::whereIn('facility_id', $facilityIds)->pluck('id')
                )->pluck('id')
            )->pluck('id'))
                ->orWhereIn('facility_id', $facilityIds);
        })->pluck('id');

        return \App\Models\TransportTrip::whereIn('certificate_id', $certificateIds)->pluck('id');
    }
}
