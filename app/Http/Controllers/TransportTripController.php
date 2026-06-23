<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportsProcessorRecords;
use App\Http\Controllers\Concerns\ScopesProcessorData;
use App\Http\Requests\ExportTransportTripsRequest;
use App\Http\Requests\StoreTransportTripRequest;
use App\Http\Requests\UpdateTransportTripRequest;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\TransportTrip;
use App\Services\Processor\CertificateTransportDefaultsService;
use App\Support\DomPdf;
use Carbon\Carbon;
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

    public function __construct(
        private readonly CertificateTransportDefaultsService $certificateTransportDefaults,
    ) {}

    public function hub(Request $request): View
    {
        $filters = $this->resolveHubFilters($request);
        $base = $this->scopedTripsQuery($request);
        $hubStats = $this->buildHubStats($base, $filters);

        $trips = (clone $base)
            ->with([
                'certificate.batch',
                'certificate.facility',
                'originFacility',
                'destinationFacility',
                'deliveryConfirmation',
            ])
            ->when($filters['is_filtered'], function ($query) use ($filters) {
                $query->whereDate('departure_date', '>=', $filters['start']->toDateString())
                    ->whereDate('departure_date', '<=', $filters['end']->toDateString());
            })
            ->latest('departure_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $exportQuery = $filters['is_filtered']
            ? ['from' => $filters['date_from'], 'to' => $filters['date_to']]
            : [];

        return view('transport-trips.hub', compact('hubStats', 'trips', 'filters', 'exportQuery'));
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('transport-trips.hub', $request->query());
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: null,
     *     end: null,
     *     range_label: string,
     *     trips_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function hubFiltersAllTime(): array
    {
        return [
            'period' => 'all',
            'date_from' => '',
            'date_to' => '',
            'start' => null,
            'end' => null,
            'range_label' => __('All time'),
            'trips_label' => __('Total trips'),
            'has_custom_range' => false,
            'is_filtered' => false,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, date_from: string, date_to: string, range_label: string, trips_label: string}
     */
    private function presetRangeForPeriod(string $period): array
    {
        $now = now();

        [$start, $end, $rangeLabel, $tripsLabel] = match ($period) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->format('M j, Y'),
                __('Departures today'),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (string) $now->year,
                __('Departures this year'),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->format('F Y'),
                __('Departures this month'),
            ],
            default => throw new \InvalidArgumentException('Invalid preset period.'),
        };

        return [
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'range_label' => $rangeLabel,
            'trips_label' => $tripsLabel,
        ];
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     range_label: string,
     *     trips_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function resolveHubFilters(Request $request): array
    {
        if (! $request->hasAny(['period', 'date_from', 'date_to'])) {
            return $this->hubFiltersAllTime();
        }

        $period = (string) $request->query('period', 'all');
        if (! in_array($period, ['all', 'day', 'month', 'year'], true)) {
            $period = 'all';
        }

        $rawFrom = trim((string) $request->query('date_from', ''));
        $rawTo = trim((string) $request->query('date_to', ''));

        if ($period === 'all' && $rawFrom === '' && $rawTo === '') {
            return $this->hubFiltersAllTime();
        }

        if ($rawFrom !== '' && $rawTo !== '') {
            $start = Carbon::parse($rawFrom)->startOfDay();
            $end = Carbon::parse($rawTo)->endOfDay();
            if ($start->gt($end)) {
                $start = Carbon::parse($rawTo)->startOfDay();
                $end = Carbon::parse($rawFrom)->endOfDay();
                [$rawFrom, $rawTo] = [$start->toDateString(), $end->toDateString()];
            }

            return [
                'period' => $period,
                'date_from' => $rawFrom,
                'date_to' => $rawTo,
                'start' => $start,
                'end' => $end,
                'range_label' => $start->format('M j, Y').' – '.$end->format('M j, Y'),
                'trips_label' => __('Departures in range'),
                'has_custom_range' => true,
                'is_filtered' => true,
            ];
        }

        if (in_array($period, ['day', 'month', 'year'], true)) {
            $preset = $this->presetRangeForPeriod($period);

            return [
                'period' => $period,
                'date_from' => $preset['date_from'],
                'date_to' => $preset['date_to'],
                'start' => $preset['start'],
                'end' => $preset['end'],
                'range_label' => $preset['range_label'],
                'trips_label' => $preset['trips_label'],
                'has_custom_range' => false,
                'is_filtered' => true,
            ];
        }

        return $this->hubFiltersAllTime();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<TransportTrip>  $base
     * @return array<string, int|string>
     */
    private function buildHubStats(\Illuminate\Database\Eloquent\Builder $base, array $filters): array
    {
        $scoped = (clone $base)->when($filters['is_filtered'], function ($query) use ($filters) {
            $query->whereDate('departure_date', '>=', $filters['start']->toDateString())
                ->whereDate('departure_date', '<=', $filters['end']->toDateString());
        });

        return [
            'trips_label' => $filters['trips_label'],
            'total_trips' => $scoped->count(),
            'pending' => (clone $base)->where('status', TransportTrip::STATUS_PENDING)->count(),
            'in_transit' => (clone $base)->where('status', TransportTrip::STATUS_IN_TRANSIT)->count(),
            'arrived' => (clone $base)->where('status', TransportTrip::STATUS_ARRIVED)->count(),
            'with_delivery_confirmation' => (clone $base)->has('deliveryConfirmation')->count(),
        ];
    }

    public function export(ExportTransportTripsRequest $request): StreamedResponse|JsonResponse|Response
    {
        $trips = $this->applyTripFilters(
            $this->scopedTripsQuery($request)->with([
                'certificate',
                'originFacility',
                'destinationFacility',
                'batch',
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
        $options = $this->formOptions($request);
        $certificateId = $request->integer('certificate_id');
        if ($certificateId > 0 && collect($options['certificates'])->contains('id', $certificateId)) {
            $options['selectedCertificateId'] = $certificateId;
            $selected = collect($options['certificates'])->firstWhere('id', $certificateId);
            $options['transportDefaults'] = $selected['transport_defaults'] ?? [];
            $options['lockedTransportFields'] = $selected['locked_fields'] ?? [];
        }

        return view('transport-trips.create', $options);
    }

    public function store(StoreTransportTripRequest $request): RedirectResponse
    {
        $this->assertTripRelationsInScope($request);

        $trip = TransportTrip::create(
            TransportTrip::normalizeDestinationAttributes($request->validated())
        );

        $trip->load('certificate');
        if ($trip->certificate) {
            $this->certificateTransportDefaults->syncTripToCertificate($trip->certificate, $trip);
        }

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
            'deliveryConfirmation.receivingFacility',
        ]);

        return view('transport-trips.show', ['trip' => $transportTrip]);
    }

    public function edit(Request $request, TransportTrip $transportTrip): View
    {
        $this->ensureTripInScope($request, $transportTrip);

        return view('transport-trips.edit', array_merge(
            [
                'trip' => $transportTrip,
                'transportDefaults' => $transportTrip->certificate
                    ? $this->certificateTransportDefaults->suggestedForCertificate($transportTrip->certificate)
                    : [],
                'lockedTransportFields' => $transportTrip->certificate
                    ? $this->certificateTransportDefaults->lockedFieldKeys($transportTrip->certificate)
                    : [],
            ],
            $this->formOptions($request)
        ));
    }

    public function update(UpdateTransportTripRequest $request, TransportTrip $transportTrip): RedirectResponse
    {
        $this->ensureTripInScope($request, $transportTrip);
        $this->assertTripRelationsInScope($request);

        $transportTrip->update(
            TransportTrip::normalizeDestinationAttributes($request->validated())
        );

        $transportTrip->load('certificate');
        if ($transportTrip->certificate) {
            $this->certificateTransportDefaults->syncTripToCertificate($transportTrip->certificate, $transportTrip);
        }

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

        return [
            'certificates' => $this->transportableCertificateOptions($certificateIds),
            'facilities' => $this->facilityOptions($request),
            'selectedCertificateId' => null,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $certificateIds
     * @return list<array<string, mixed>>
     */
    protected function transportableCertificateOptions(\Illuminate\Support\Collection $certificateIds): array
    {
        return Certificate::query()
            ->with([
                'batch',
                'facility',
                'transportTrips' => fn ($query) => $query->orderByDesc('departure_date'),
            ])
            ->whereIn('id', $certificateIds)
            ->where('status', Certificate::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', today());
            })
            ->latest('issued_at')
            ->get()
            ->map(function (Certificate $certificate) {
                $transportDefaults = $this->certificateTransportDefaults->suggestedForCertificate($certificate);

                return [
                    'id' => $certificate->id,
                    'label' => ($certificate->certificate_number ?: '#'.$certificate->id)
                        .($certificate->batch ? ' — '.$certificate->batch->batch_code : '')
                        .($certificate->issued_at ? ' ('.$certificate->issued_at->format('d M Y').')' : ''),
                    'batch_id' => $certificate->batch_id,
                    'batch_label' => $certificate->batch?->batch_code ?? '—',
                    'facility_id' => $certificate->facility_id,
                    'facility_label' => $certificate->facility?->facility_name ?? '—',
                    'transport_defaults' => $transportDefaults,
                    'locked_fields' => $this->certificateTransportDefaults->lockedFieldKeys($certificate),
                ];
            })
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
}
