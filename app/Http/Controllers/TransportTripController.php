<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportsProcessorRecords;
use App\Http\Controllers\Concerns\ScopesProcessorData;
use App\Http\Requests\ExportTransportTripsRequest;
use App\Http\Requests\StoreTransportTripRequest;
use App\Http\Requests\UpdateTransportTripRequest;
use App\Models\Batch;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\TransportTrip;
use App\Models\WarehouseStorage;
use App\Support\DomPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransportTripController extends Controller
{
    use ExportsProcessorRecords;
    use ScopesProcessorData;

    public function hub(Request $request): View
    {
        $base = $this->scopedTripsQuery($request);

        return view('transport-trips.hub', [
            'totalTrips' => (clone $base)->count(),
            'pendingCount' => (clone $base)->where('status', TransportTrip::STATUS_PENDING)->count(),
            'inTransitCount' => (clone $base)->where('status', TransportTrip::STATUS_IN_TRANSIT)->count(),
            'arrivedCount' => (clone $base)->where('status', TransportTrip::STATUS_ARRIVED)->count(),
            'completedCount' => (clone $base)->where('status', TransportTrip::STATUS_COMPLETED)->count(),
            'tripsWithDeliveryConfirmationCount' => (clone $base)->has('deliveryConfirmation')->count(),
            'facilities' => $this->facilityOptions($request),
            'filters' => $request->only(['status', 'from', 'to', 'origin_facility_id', 'destination_facility_id']),
        ]);
    }

    public function index(Request $request): View
    {
        $certificateIds = $this->accessibleCertificateIds($request);
        $query = $this->applyTripFilters($this->scopedTripsQuery($request)->with([
            'certificate.batch',
            'certificate.facility',
            'originFacility',
            'destinationFacility',
        ]), $request);

        $trips = $query->latest('departure_date')->paginate(10)->withQueryString();

        $base = TransportTrip::query()->whereIn('certificate_id', $certificateIds);

        return view('transport-trips.index', [
            'trips' => $trips,
            'kpis' => [
                'total' => (clone $base)->count(),
                'arrived' => (clone $base)->where('status', TransportTrip::STATUS_ARRIVED)->count(),
                'completed' => (clone $base)->where('status', TransportTrip::STATUS_COMPLETED)->count(),
            ],
            'facilities' => $this->facilityOptions($request),
            'filters' => $request->only(['status', 'from', 'to', 'origin_facility_id', 'destination_facility_id']),
        ]);
    }

    public function export(ExportTransportTripsRequest $request): StreamedResponse|JsonResponse|Response
    {
        $trips = $this->applyTripFilters(
            $this->scopedTripsQuery($request)->with([
                'certificate',
                'originFacility',
                'destinationFacility',
                'batch',
                'warehouseStorage',
                'deliveryConfirmation.client',
                'deliveryConfirmation.receivingFacility',
            ]),
            $request
        )->orderByDesc('departure_date')->get();

        $format = $request->input('format', 'csv');

        return match ($format) {
            'excel' => $this->streamExcel($trips, $this->tripCsvColumns(), 'transport_trips'),
            'pdf' => $this->streamTripsListPdf($trips, 'transport-trips.export-pdf', [
                'filters' => $request->validated(),
            ]),
            'json' => $this->jsonExport($trips),
            default => $this->streamCsv($trips, $this->tripCsvColumns(), 'transport-trips-'.now()->format('Ymd-His').'.csv'),
        };
    }

    public function exportTraceability(ExportTransportTripsRequest $request): Response
    {
        $trips = $this->applyTripFilters(
            $this->scopedTripsQuery($request)->with([
                'certificate.batch',
                'certificate.facility',
                'originFacility',
                'destinationFacility',
                'batch',
                'warehouseStorage.batch',
                'warehouseStorage.warehouseFacility',
                'deliveryConfirmation.receivingFacility',
                'deliveryConfirmation.client',
                'deliveryConfirmation.contract',
                'deliveryConfirmation.fulfillingDemand',
                'deliveryConfirmation.exportDocuments',
            ]),
            $request
        )->orderByDesc('departure_date')->get();

        $business = Business::query()->find($request->user()->activeProcessorBusinessId());

        $fileName = 'transport-traceability-'.now()->format('Ymd-His').'.pdf';
        $pdf = DomPdf::loadView('transport-trips.export-traceability', [
            'trips' => $trips,
            'business' => $business,
            'filters' => $request->validated(),
            'generatedAt' => now(),
            'generatedBy' => $request->user()->name,
            'statusBreakdown' => $trips->groupBy('status')->map->count(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }

    public function create(Request $request): View
    {
        return view('transport-trips.create', $this->formOptions($request));
    }

    public function store(StoreTransportTripRequest $request): RedirectResponse
    {
        $this->assertTripRelationsInScope($request);
        if ($redirect = $this->warehouseStorageRedirectIfInvalid($request)) {
            return $redirect;
        }

        $trip = TransportTrip::create(
            TransportTrip::normalizeDestinationAttributes($request->validated())
        );

        return redirect()
            ->route('transport-trips.show', $trip)
            ->with('status', __('Transport trip recorded. Confirm delivery when the load arrives.'));
    }

    public function show(Request $request, TransportTrip $transportTrip): View
    {
        $this->ensureTripInScope($request, $transportTrip);
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

    public function edit(Request $request, TransportTrip $transportTrip): View
    {
        $this->ensureTripInScope($request, $transportTrip);

        return view('transport-trips.edit', array_merge(
            ['trip' => $transportTrip],
            $this->formOptions($request)
        ));
    }

    public function update(UpdateTransportTripRequest $request, TransportTrip $transportTrip): RedirectResponse
    {
        $this->ensureTripInScope($request, $transportTrip);
        $this->assertTripRelationsInScope($request);
        if ($redirect = $this->warehouseStorageRedirectIfInvalid($request)) {
            return $redirect;
        }

        $transportTrip->update(
            TransportTrip::normalizeDestinationAttributes($request->validated())
        );

        return redirect()->route('transport-trips.hub')
            ->with('status', __('Transport trip updated successfully.'));
    }

    public function destroy(Request $request, TransportTrip $transportTrip): RedirectResponse
    {
        $this->ensureTripInScope($request, $transportTrip);

        if ($transportTrip->deliveryConfirmation()->exists()) {
            return redirect()->back()
                ->withErrors(['trip' => __('Cannot delete this trip while a delivery confirmation is linked. Remove the confirmation first.')]);
        }

        $transportTrip->delete();

        return redirect()->route('transport-trips.hub')
            ->with('status', __('Transport trip removed.'));
    }

    /**
     * @return array<string, callable>
     */
    protected function tripCsvColumns(): array
    {
        return [
            'Trip ID' => fn (TransportTrip $t) => $t->id,
            'Certificate number' => fn (TransportTrip $t) => $t->certificate?->certificate_number ?? '',
            'Batch code' => fn (TransportTrip $t) => $t->batch?->batch_code ?? '',
            'Origin facility' => fn (TransportTrip $t) => $t->originFacility?->facility_name ?? '',
            'Destination' => fn (TransportTrip $t) => $t->destination_display,
            'Destination type' => fn (TransportTrip $t) => $t->isExternalDestination() ? __('External') : __('Facility'),
            'Vehicle plate' => fn (TransportTrip $t) => $t->vehicle_plate_number,
            'Driver name' => fn (TransportTrip $t) => $t->driver_name,
            'Driver phone' => fn (TransportTrip $t) => $t->driver_phone ?? '',
            'Departure date' => fn (TransportTrip $t) => $t->departure_date?->format('Y-m-d') ?? '',
            'Arrival date' => fn (TransportTrip $t) => $t->arrival_date?->format('Y-m-d') ?? '',
            'Status' => fn (TransportTrip $t) => $t->status,
            'Warehouse storage linked' => fn (TransportTrip $t) => $t->warehouse_storage_id ? __('Yes') : __('No'),
            'Has delivery confirmation' => fn (TransportTrip $t) => $t->deliveryConfirmation ? __('Yes') : __('No'),
            'Delivery status' => fn (TransportTrip $t) => $t->deliveryConfirmation?->confirmation_status ?? '',
            'Received quantity' => fn (TransportTrip $t) => $t->deliveryConfirmation?->received_quantity ?? '',
            'Received unit' => fn (TransportTrip $t) => $t->deliveryConfirmation?->received_unit ?? '',
            'Receiver name' => fn (TransportTrip $t) => $t->deliveryConfirmation?->receiver_name ?? '',
            'Receiver country' => fn (TransportTrip $t) => $t->deliveryConfirmation?->receiver_country ?? '',
            'Client' => fn (TransportTrip $t) => $t->deliveryConfirmation?->client?->display_name ?? '',
        ];
    }

    protected function applyTripFilters(\Illuminate\Database\Eloquent\Builder $query, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $facilityIds = $this->accessibleFacilityIds($request);

        return $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('departure_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('departure_date', '<=', $request->date('to')))
            ->when(
                $request->filled('origin_facility_id') && $facilityIds->contains((int) $request->origin_facility_id),
                fn ($q) => $q->where('origin_facility_id', $request->integer('origin_facility_id'))
            )
            ->when(
                $request->filled('destination_facility_id') && $facilityIds->contains((int) $request->destination_facility_id),
                fn ($q) => $q->where('destination_facility_id', $request->integer('destination_facility_id'))
            );
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(Request $request): array
    {
        $certificateIds = $this->accessibleCertificateIds($request);

        $releasedStorages = $this->releasedStorageOptions($certificateIds);

        return [
            'certificates' => Certificate::with('batch')
                ->whereIn('id', $certificateIds)
                ->latest('issued_at')
                ->get()
                ->map(fn (Certificate $c) => [
                    'id' => $c->id,
                    'label' => ($c->certificate_number ?: '#'.$c->id).($c->batch ? ' — '.$c->batch->batch_code : ''),
                ]),
            'facilities' => $this->facilityOptions($request),
            'batches' => Batch::whereIn('id', $this->accessibleBatchIds($request))
                ->orderByDesc('id')
                ->get()
                ->map(fn (Batch $b) => ['id' => $b->id, 'label' => $b->batch_code]),
            'releasedStorages' => $releasedStorages,
            'hasReleasedStorages' => $releasedStorages !== [],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $certificateIds
     * @return list<array<string, mixed>>
     */
    protected function releasedStorageOptions(\Illuminate\Support\Collection $certificateIds): array
    {
        return WarehouseStorage::with(['batch', 'certificate', 'warehouseFacility'])
            ->whereIn('certificate_id', $certificateIds)
            ->where('status', WarehouseStorage::STATUS_RELEASED)
            ->latest('released_date')
            ->get()
            ->map(fn (WarehouseStorage $ws) => [
                'id' => $ws->id,
                'label' => ($ws->batch?->batch_code ?? '#').' — '.($ws->warehouseFacility?->facility_name ?? '').' ('.__('released').')',
                'certificate_id' => $ws->certificate_id,
                'certificate_label' => ($ws->certificate?->certificate_number ?: '#'.$ws->certificate_id)
                    .($ws->batch ? ' — '.$ws->batch->batch_code : ''),
                'batch_id' => $ws->batch_id,
                'batch_label' => $ws->batch?->batch_code ?? '—',
                'warehouse_facility_id' => $ws->warehouse_facility_id,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    protected function facilityOptions(Request $request): array
    {
        return Facility::whereIn('id', $this->accessibleFacilityIds($request))
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name])
            ->values()
            ->all();
    }

    protected function assertTripRelationsInScope(Request $request): void
    {
        if (! $this->accessibleCertificateIds($request)->contains((int) $request->validated('certificate_id'))) {
            abort(404);
        }

        $facilityIds = $this->accessibleFacilityIds($request);
        if (! $facilityIds->contains((int) $request->validated('origin_facility_id'))) {
            abort(404);
        }

        $destinationFacilityId = $request->validated('destination_facility_id');
        if ($destinationFacilityId !== null && $destinationFacilityId !== ''
            && ! $facilityIds->contains((int) $destinationFacilityId)) {
            abort(404);
        }
    }

    protected function warehouseStorageRedirectIfInvalid(Request $request): ?RedirectResponse
    {
        $wid = $request->validated('warehouse_storage_id');
        if (! $wid) {
            return null;
        }

        $ws = WarehouseStorage::find($wid);
        if (! $ws || ! $this->accessibleCertificateIds($request)->contains($ws->certificate_id)) {
            abort(404);
        }
        if ($ws->status !== WarehouseStorage::STATUS_RELEASED) {
            return redirect()->back()
                ->withErrors(['warehouse_storage_id' => __('Cannot transport: storage must be released first.')])
                ->withInput();
        }

        return null;
    }
}
