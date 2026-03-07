<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDemandRequest;
use App\Http\Requests\UpdateDemandRequest;
use App\Models\Business;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\DeliveryConfirmation;
use App\Models\Species;
use App\Models\TransportTrip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemandController extends Controller
{
    private function userBusinessIds(Request $request): \Illuminate\Support\Collection
    {
        return $request->user()->businesses()->pluck('id');
    }

    private function authorizeDemand(Request $request, Demand $demand): void
    {
        if (! $this->userBusinessIds($request)->contains($demand->business_id)) {
            abort(404);
        }
    }

    /** Delivery confirmations the user can see (trips from their certificates). */
    private function userTransportTripIds(Request $request): \Illuminate\Support\Collection
    {
        $facilityIds = Facility::whereIn('business_id', $this->userBusinessIds($request))->pluck('id');
        $certificateIds = \App\Models\Certificate::where(function ($q) use ($facilityIds) {
            $q->whereIn('batch_id', \App\Models\Batch::whereIn('slaughter_execution_id',
                \App\Models\SlaughterExecution::whereIn('slaughter_plan_id',
                    \App\Models\SlaughterPlan::whereIn('facility_id', $facilityIds)->pluck('id')
                )->pluck('id')
            )->pluck('id'))
            ->orWhereIn('facility_id', $facilityIds);
        })->pluck('id');
        return TransportTrip::whereIn('certificate_id', $certificateIds)->pluck('id');
    }

    public function index(Request $request): View
    {
        $businessIds = $this->userBusinessIds($request);
        $demands = Demand::with(['business', 'destinationFacility', 'client'])
            ->whereIn('business_id', $businessIds)
            ->latest('requested_delivery_date')
            ->paginate(10)
            ->withQueryString();

        return view('demands.index', compact('demands'));
    }

    public function create(Request $request): View
    {
        $businessIds = $this->userBusinessIds($request);
        $businesses = Business::whereIn('id', $businessIds)->orderBy('business_name')->get();
        $facilities = Facility::whereIn('business_id', $businessIds)->orderBy('facility_name')->get();
        $clients = Client::whereIn('business_id', $businessIds)->where('is_active', true)->orderBy('name')->get();
        $contracts = Contract::whereIn('business_id', $businessIds)->orderBy('contract_number')->get();
        $speciesOptions = Species::active()->pluck('name')->toArray();
        if (empty($speciesOptions)) {
            $speciesOptions = \App\Models\AnimalIntake::SPECIES_OPTIONS;
        }

        return view('demands.create', compact('businesses', 'facilities', 'clients', 'contracts', 'speciesOptions'));
    }

    public function store(StoreDemandRequest $request): RedirectResponse
    {
        $businessIds = $this->userBusinessIds($request);
        if (! $businessIds->contains((int) $request->validated('business_id'))) {
            abort(404);
        }
        $facilityId = $request->validated('destination_facility_id');
        if ($facilityId && ! Facility::where('id', $facilityId)->whereIn('business_id', $businessIds)->exists()) {
            abort(404);
        }
        $clientId = $request->validated('client_id');
        if ($clientId && ! Client::where('id', $clientId)->whereIn('business_id', $businessIds)->exists()) {
            abort(404);
        }

        $data = $request->validated();
        if (empty($data['demand_number'])) {
            $data['demand_number'] = null;
        }
        $demand = Demand::create($data);
        if (empty($demand->demand_number)) {
            $demand->update(['demand_number' => sprintf('DEM-%s-%04d', date('Y'), $demand->id)]);
        }

        return redirect()->route('demands.index')->with('status', __('Demand created.'));
    }

    public function show(Request $request, Demand $demand): View
    {
        $this->authorizeDemand($request, $demand);
        $demand->load(['business', 'destinationFacility', 'client', 'contract', 'fulfilledByDelivery']);

        return view('demands.show', compact('demand'));
    }

    public function edit(Request $request, Demand $demand): View
    {
        $this->authorizeDemand($request, $demand);
        $businessIds = $this->userBusinessIds($request);
        $businesses = Business::whereIn('id', $businessIds)->orderBy('business_name')->get();
        $facilities = Facility::whereIn('business_id', $businessIds)->orderBy('facility_name')->get();
        $clients = Client::whereIn('business_id', $businessIds)->where('is_active', true)->orderBy('name')->get();
        $contracts = Contract::whereIn('business_id', $businessIds)->orderBy('contract_number')->get();
        $speciesOptions = Species::active()->pluck('name')->toArray();
        if (empty($speciesOptions)) {
            $speciesOptions = \App\Models\AnimalIntake::SPECIES_OPTIONS;
        }

        $tripIds = $this->userTransportTripIds($request);
        $candidateDeliveries = collect();
        if ($demand->client_id || $demand->destination_facility_id) {
            $candidateDeliveries = DeliveryConfirmation::with(['transportTrip', 'client', 'receivingFacility'])
                ->whereIn('transport_trip_id', $tripIds)
                ->where(function ($q) use ($demand) {
                    if ($demand->client_id) {
                        $q->orWhere('client_id', $demand->client_id);
                    }
                    if ($demand->destination_facility_id) {
                        $q->orWhere('receiving_facility_id', $demand->destination_facility_id);
                    }
                })
                ->latest('received_date')
                ->limit(100)
                ->get()
                ->keyBy('id');
            if ($demand->fulfilled_by_delivery_id && ! $candidateDeliveries->has($demand->fulfilled_by_delivery_id)) {
                $current = DeliveryConfirmation::with(['transportTrip', 'client', 'receivingFacility'])
                    ->whereIn('transport_trip_id', $tripIds)
                    ->find($demand->fulfilled_by_delivery_id);
                if ($current) {
                    $candidateDeliveries->put($current->id, $current);
                }
            }
            $candidateDeliveries = $candidateDeliveries->sortByDesc('received_date')->values();
        }

        return view('demands.edit', compact('demand', 'businesses', 'facilities', 'clients', 'contracts', 'speciesOptions', 'candidateDeliveries'));
    }

    public function update(UpdateDemandRequest $request, Demand $demand): RedirectResponse
    {
        $this->authorizeDemand($request, $demand);
        $businessIds = $this->userBusinessIds($request);
        if (! $businessIds->contains((int) $request->validated('business_id'))) {
            abort(404);
        }
        $facilityId = $request->validated('destination_facility_id');
        if ($facilityId && ! Facility::where('id', $facilityId)->whereIn('business_id', $businessIds)->exists()) {
            abort(404);
        }
        $clientId = $request->validated('client_id');
        if ($clientId && ! Client::where('id', $clientId)->whereIn('business_id', $businessIds)->exists()) {
            abort(404);
        }

        $data = $request->validated();
        $fulfilledByDeliveryId = isset($data['fulfilled_by_delivery_id']) && $data['fulfilled_by_delivery_id'] !== '' ? (int) $data['fulfilled_by_delivery_id'] : null;
        if ($fulfilledByDeliveryId) {
            $allowedTripIds = $this->userTransportTripIds($request);
            $delivery = DeliveryConfirmation::where('id', $fulfilledByDeliveryId)->whereIn('transport_trip_id', $allowedTripIds)->first();
            if ($delivery && ($delivery->client_id == $demand->client_id || $delivery->receiving_facility_id == $demand->destination_facility_id)) {
                $data['fulfilled_by_delivery_id'] = $fulfilledByDeliveryId;
                $data['status'] = Demand::STATUS_FULFILLED;
            } else {
                $data['fulfilled_by_delivery_id'] = null;
            }
        } else {
            $data['fulfilled_by_delivery_id'] = null;
        }
        $demand->update($data);

        return redirect()->route('demands.show', $demand)->with('status', __('Demand updated.'));
    }

    public function destroy(Request $request, Demand $demand): RedirectResponse
    {
        $this->authorizeDemand($request, $demand);
        $demand->delete();

        return redirect()->route('demands.index')->with('status', __('Demand deleted.'));
    }
}
