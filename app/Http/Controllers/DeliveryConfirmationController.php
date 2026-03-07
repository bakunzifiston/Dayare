<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryConfirmationRequest;
use App\Http\Requests\UpdateDeliveryConfirmationRequest;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryConfirmationController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->businesses()->pluck('id'))
            ->pluck('id');
    }

    private function userSlaughterPlanIds(Request $request): \Illuminate\Support\Collection
    {
        return SlaughterPlan::whereIn('facility_id', $this->userFacilityIds($request))
            ->pluck('id');
    }

    private function userExecutionIds(Request $request): \Illuminate\Support\Collection
    {
        return SlaughterExecution::whereIn('slaughter_plan_id', $this->userSlaughterPlanIds($request))
            ->pluck('id');
    }

    private function userBatchIds(Request $request): \Illuminate\Support\Collection
    {
        return Batch::whereIn('slaughter_execution_id', $this->userExecutionIds($request))
            ->pluck('id');
    }

    private function userCertificateIds(Request $request): \Illuminate\Support\Collection
    {
        $batchIds = $this->userBatchIds($request);
        $facilityIds = $this->userFacilityIds($request);

        return Certificate::where(function ($q) use ($batchIds, $facilityIds) {
            $q->whereIn('batch_id', $batchIds)
                ->orWhere(function ($q2) use ($facilityIds) {
                    $q2->whereNull('batch_id')->whereIn('facility_id', $facilityIds);
                });
        })->pluck('id');
    }

    private function userTransportTripIds(Request $request): \Illuminate\Support\Collection
    {
        return TransportTrip::whereIn('certificate_id', $this->userCertificateIds($request))
            ->pluck('id');
    }

    private function authorizeConfirmation(Request $request, DeliveryConfirmation $confirmation): void
    {
        if (! $this->userTransportTripIds($request)->contains($confirmation->transport_trip_id)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $tripIds = $this->userTransportTripIds($request);

        $confirmations = DeliveryConfirmation::with([
            'transportTrip.certificate',
            'transportTrip.originFacility',
            'transportTrip.destinationFacility',
            'receivingFacility',
            'client',
        ])
            ->whereIn('transport_trip_id', $tripIds)
            ->latest('received_date')
            ->paginate(10);

        $kpis = [
            'total' => DeliveryConfirmation::whereIn('transport_trip_id', $tripIds)->count(),
            'confirmed' => DeliveryConfirmation::whereIn('transport_trip_id', $tripIds)->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)->count(),
        ];

        return view('delivery-confirmations.index', compact('confirmations', 'kpis'));
    }

    public function create(Request $request): View
    {
        $tripIds = $this->userTransportTripIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $trips = TransportTrip::with('certificate', 'originFacility', 'destinationFacility')
            ->whereIn('id', $tripIds)
            ->whereDoesntHave('deliveryConfirmation')
            ->latest('departure_date')
            ->get()
            ->map(fn (TransportTrip $t) => [
                'id' => $t->id,
                'label' => $t->vehicle_plate_number . ' — ' . ($t->originFacility->facility_name ?? '') . ' → ' . ($t->destinationFacility->facility_name ?? '') . ' (' . $t->departure_date->format('d M Y') . ')',
            ]);

        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);

        $businessIds = $request->user()->businesses()->pluck('id');
        $clients = Client::whereIn('business_id', $businessIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'label' => $c->display_name,
                'name' => $c->name,
                'country' => $c->country ?? '',
                'address' => $c->address_line,
            ]);

        return view('delivery-confirmations.create', [
            'trips' => $trips,
            'facilities' => $facilities,
            'clients' => $clients,
        ]);
    }

    public function store(StoreDeliveryConfirmationRequest $request): RedirectResponse
    {
        $tripIds = $this->userTransportTripIds($request);
        $facilityIds = $this->userFacilityIds($request);
        if (! $tripIds->contains((int) $request->validated('transport_trip_id'))) {
            abort(404);
        }
        $receivingFacilityId = $request->validated('receiving_facility_id');
        if ($receivingFacilityId !== null && $receivingFacilityId !== '' && ! $facilityIds->contains((int) $receivingFacilityId)) {
            abort(404);
        }
        $clientId = $request->validated('client_id');
        if ($clientId && ! $request->user()->businesses()->pluck('id')->contains(Client::find($clientId)?->business_id)) {
            abort(404);
        }

        DeliveryConfirmation::create($request->validated());

        return redirect()->route('delivery-confirmations.index')
            ->with('status', __('Delivery confirmation recorded successfully.'));
    }

    public function show(Request $request, DeliveryConfirmation $deliveryConfirmation): View|RedirectResponse
    {
        $this->authorizeConfirmation($request, $deliveryConfirmation);
        $deliveryConfirmation->load([
            'transportTrip.certificate.batch',
            'transportTrip.originFacility',
            'transportTrip.destinationFacility',
            'receivingFacility',
            'client',
        ]);

        return view('delivery-confirmations.show', ['confirmation' => $deliveryConfirmation]);
    }

    public function edit(Request $request, DeliveryConfirmation $deliveryConfirmation): View|RedirectResponse
    {
        $this->authorizeConfirmation($request, $deliveryConfirmation);

        $tripIds = $this->userTransportTripIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $trips = TransportTrip::with('originFacility', 'destinationFacility')
            ->whereIn('id', $tripIds)
            ->get()
            ->map(fn (TransportTrip $t) => [
                'id' => $t->id,
                'label' => $t->vehicle_plate_number . ' — ' . ($t->originFacility->facility_name ?? '') . ' → ' . ($t->destinationFacility->facility_name ?? ''),
            ]);

        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);

        $businessIds = $request->user()->businesses()->pluck('id');
        $clients = Client::whereIn('business_id', $businessIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'label' => $c->display_name,
                'name' => $c->name,
                'country' => $c->country ?? '',
                'address' => $c->address_line,
            ]);

        return view('delivery-confirmations.edit', [
            'confirmation' => $deliveryConfirmation,
            'trips' => $trips,
            'facilities' => $facilities,
            'clients' => $clients,
        ]);
    }

    public function update(UpdateDeliveryConfirmationRequest $request, DeliveryConfirmation $deliveryConfirmation): RedirectResponse
    {
        $this->authorizeConfirmation($request, $deliveryConfirmation);
        $tripIds = $this->userTransportTripIds($request);
        $facilityIds = $this->userFacilityIds($request);
        if (! $tripIds->contains((int) $request->validated('transport_trip_id'))) {
            abort(404);
        }
        $receivingFacilityId = $request->validated('receiving_facility_id');
        if ($receivingFacilityId !== null && $receivingFacilityId !== '' && ! $facilityIds->contains((int) $receivingFacilityId)) {
            abort(404);
        }
        $clientId = $request->validated('client_id');
        if ($clientId && ! $request->user()->businesses()->pluck('id')->contains(Client::find($clientId)?->business_id)) {
            abort(404);
        }

        $deliveryConfirmation->update($request->validated());

        return redirect()->route('delivery-confirmations.index')
            ->with('status', __('Delivery confirmation updated successfully.'));
    }

    public function destroy(Request $request, DeliveryConfirmation $deliveryConfirmation): RedirectResponse
    {
        $this->authorizeConfirmation($request, $deliveryConfirmation);
        $deliveryConfirmation->delete();

        return redirect()->route('delivery-confirmations.index')
            ->with('status', __('Delivery confirmation removed.'));
    }
}
