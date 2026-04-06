<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransportTripRequest;
use App\Http\Requests\UpdateTransportTripRequest;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
use App\Models\WarehouseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransportTripController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
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

    private function authorizeTrip(Request $request, TransportTrip $trip): void
    {
        if (! $this->userCertificateIds($request)->contains($trip->certificate_id)) {
            abort(404);
        }
    }

    public function hub(Request $request): View
    {
        $certificateIds = $this->userCertificateIds($request);
        $base = TransportTrip::query()->whereIn('certificate_id', $certificateIds);

        $totalTrips = (clone $base)->count();
        $pendingCount = (clone $base)->where('status', TransportTrip::STATUS_PENDING)->count();
        $inTransitCount = (clone $base)->where('status', TransportTrip::STATUS_IN_TRANSIT)->count();
        $arrivedCount = (clone $base)->where('status', TransportTrip::STATUS_ARRIVED)->count();
        $completedCount = (clone $base)->where('status', TransportTrip::STATUS_COMPLETED)->count();
        $tripsWithDeliveryConfirmationCount = (clone $base)->has('deliveryConfirmation')->count();

        return view('transport-trips.hub', compact(
            'totalTrips',
            'pendingCount',
            'inTransitCount',
            'arrivedCount',
            'completedCount',
            'tripsWithDeliveryConfirmationCount',
        ));
    }

    public function index(Request $request): View
    {
        $certificateIds = $this->userCertificateIds($request);

        $trips = TransportTrip::with([
            'certificate.batch',
            'certificate.facility',
            'originFacility',
            'destinationFacility',
        ])
            ->whereIn('certificate_id', $certificateIds)
            ->latest('departure_date')
            ->paginate(10);

        $kpis = [
            'total' => TransportTrip::whereIn('certificate_id', $certificateIds)->count(),
            'arrived' => TransportTrip::whereIn('certificate_id', $certificateIds)->where('status', TransportTrip::STATUS_ARRIVED)->count(),
            'completed' => TransportTrip::whereIn('certificate_id', $certificateIds)->where('status', TransportTrip::STATUS_COMPLETED)->count(),
        ];

        return view('transport-trips.index', compact('trips', 'kpis'));
    }

    public function create(Request $request): View
    {
        $certificateIds = $this->userCertificateIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $certificates = Certificate::with('batch')
            ->whereIn('id', $certificateIds)
            ->latest('issued_at')
            ->get()
            ->map(fn (Certificate $c) => [
                'id' => $c->id,
                'label' => ($c->certificate_number ?: '#'.$c->id).($c->batch ? ' — '.$c->batch->batch_code : ''),
            ]);

        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);

        $batches = Batch::whereIn('id', $this->userBatchIds($request))
            ->orderByDesc('id')
            ->get()
            ->map(fn (Batch $b) => ['id' => $b->id, 'label' => $b->batch_code]);

        $releasedStorages = WarehouseStorage::with(['batch', 'warehouseFacility'])
            ->whereIn('certificate_id', $certificateIds)
            ->where('status', WarehouseStorage::STATUS_RELEASED)
            ->latest('released_date')
            ->get()
            ->map(fn (WarehouseStorage $ws) => [
                'id' => $ws->id,
                'label' => $ws->batch->batch_code.' — '.($ws->warehouseFacility->facility_name ?? '').' ('.__('released').')',
            ]);

        return view('transport-trips.create', [
            'certificates' => $certificates,
            'facilities' => $facilities,
            'batches' => $batches,
            'releasedStorages' => $releasedStorages,
        ]);
    }

    public function store(StoreTransportTripRequest $request): RedirectResponse
    {
        if (! $this->userCertificateIds($request)->contains((int) $request->validated('certificate_id'))) {
            abort(404);
        }
        $facilityIds = $this->userFacilityIds($request);
        if (! $facilityIds->contains((int) $request->validated('origin_facility_id')) ||
            ! $facilityIds->contains((int) $request->validated('destination_facility_id'))) {
            abort(404);
        }
        $wid = $request->validated('warehouse_storage_id');
        if ($wid) {
            $ws = WarehouseStorage::find($wid);
            if (! $ws || ! $this->userCertificateIds($request)->contains($ws->certificate_id)) {
                abort(404);
            }
            if ($ws->status !== WarehouseStorage::STATUS_RELEASED) {
                return redirect()->back()->withErrors(['warehouse_storage_id' => __('Cannot transport: storage must be released first.')])->withInput();
            }
        }

        TransportTrip::create($request->validated());

        return redirect()->route('transport-trips.hub')
            ->with('status', __('Transport trip recorded successfully.'));
    }

    public function show(Request $request, TransportTrip $transportTrip): View|RedirectResponse
    {
        $this->authorizeTrip($request, $transportTrip);
        $transportTrip->load([
            'certificate.batch.slaughterExecution',
            'certificate.facility',
            'certificate.inspector',
            'originFacility',
            'destinationFacility',
            'warehouseStorage.batch',
            'deliveryConfirmation.receivingFacility',
        ]);

        return view('transport-trips.show', ['trip' => $transportTrip]);
    }

    public function edit(Request $request, TransportTrip $transportTrip): View|RedirectResponse
    {
        $this->authorizeTrip($request, $transportTrip);

        $certificateIds = $this->userCertificateIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $certificates = Certificate::with('batch')
            ->whereIn('id', $certificateIds)
            ->latest('issued_at')
            ->get()
            ->map(fn (Certificate $c) => [
                'id' => $c->id,
                'label' => ($c->certificate_number ?: '#'.$c->id).($c->batch ? ' — '.$c->batch->batch_code : ''),
            ]);

        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);

        $batches = Batch::whereIn('id', $this->userBatchIds($request))
            ->orderByDesc('id')
            ->get()
            ->map(fn (Batch $b) => ['id' => $b->id, 'label' => $b->batch_code]);

        $releasedStorages = WarehouseStorage::with(['batch', 'warehouseFacility'])
            ->whereIn('certificate_id', $certificateIds)
            ->where('status', WarehouseStorage::STATUS_RELEASED)
            ->latest('released_date')
            ->get()
            ->map(fn (WarehouseStorage $ws) => [
                'id' => $ws->id,
                'label' => $ws->batch->batch_code.' — '.($ws->warehouseFacility->facility_name ?? '').' ('.__('released').')',
            ]);

        return view('transport-trips.edit', [
            'trip' => $transportTrip,
            'certificates' => $certificates,
            'facilities' => $facilities,
            'batches' => $batches,
            'releasedStorages' => $releasedStorages,
        ]);
    }

    public function update(UpdateTransportTripRequest $request, TransportTrip $transportTrip): RedirectResponse
    {
        $this->authorizeTrip($request, $transportTrip);
        if (! $this->userCertificateIds($request)->contains((int) $request->validated('certificate_id'))) {
            abort(404);
        }
        $facilityIds = $this->userFacilityIds($request);
        if (! $facilityIds->contains((int) $request->validated('origin_facility_id')) ||
            ! $facilityIds->contains((int) $request->validated('destination_facility_id'))) {
            abort(404);
        }
        $wid = $request->validated('warehouse_storage_id');
        if ($wid) {
            $ws = WarehouseStorage::find($wid);
            if (! $ws || ! $this->userCertificateIds($request)->contains($ws->certificate_id)) {
                abort(404);
            }
            if ($ws->status !== WarehouseStorage::STATUS_RELEASED) {
                return redirect()->back()->withErrors(['warehouse_storage_id' => __('Cannot transport: storage must be released first.')])->withInput();
            }
        }

        $transportTrip->update($request->validated());

        return redirect()->route('transport-trips.hub')
            ->with('status', __('Transport trip updated successfully.'));
    }

    public function destroy(Request $request, TransportTrip $transportTrip): RedirectResponse
    {
        $this->authorizeTrip($request, $transportTrip);
        $transportTrip->delete();

        return redirect()->route('transport-trips.hub')
            ->with('status', __('Transport trip removed.'));
    }
}
