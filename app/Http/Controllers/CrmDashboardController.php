<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CrmDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $businessIds = $request->user()->accessibleBusinessIds();
        if ($businessIds->isEmpty()) {
            return view('crm.dashboard', [
                'totalClients' => 0,
                'openDemandsCount' => 0,
                'deliveriesThisMonth' => 0,
                'suppliersWithContractCount' => 0,
                'recentClients' => collect(),
                'openDemands' => collect(),
                'recipients' => collect(),
                'suppliersWithContract' => collect(),
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

        $suppliersWithContractIds = Contract::whereIn('business_id', $businessIds)
            ->where('contract_category', Contract::CATEGORY_SUPPLIER)
            ->whereNotNull('supplier_id')
            ->pluck('supplier_id')
            ->unique()
            ->filter();
        $suppliersWithContractCount = $suppliersWithContractIds->count();
        $suppliersWithContract = Supplier::with(['business', 'contracts' => fn ($q) => $q->where('contract_category', Contract::CATEGORY_SUPPLIER)->orderByDesc('start_date')])
            ->whereIn('business_id', $businessIds)
            ->whereIn('id', $suppliersWithContractIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get();

        return view('crm.dashboard', compact(
            'totalClients',
            'openDemandsCount',
            'deliveriesThisMonth',
            'suppliersWithContractCount',
            'recentClients',
            'openDemands',
            'recipients',
            'suppliersWithContract'
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
        $businessIds = $request->user()->accessibleBusinessIds();
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
