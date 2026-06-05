<?php

namespace App\Http\Controllers;

use App\Enums\MeatExportDocumentType;
use App\Enums\ReceivedUnit;
use App\Http\Controllers\Concerns\ExportsProcessorRecords;
use App\Http\Controllers\Concerns\ScopesProcessorData;
use App\Http\Requests\ExportDeliveryConfirmationsRequest;
use App\Http\Requests\StoreDeliveryConfirmationRequest;
use App\Http\Requests\UpdateDeliveryConfirmationRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\MeatExportDocument;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\TransportTrip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryConfirmationController extends Controller
{
    use ExportsProcessorRecords;
    use ScopesProcessorData;

    public function index(Request $request): View
    {
        $facilityIds = $this->accessibleFacilityIds($request);
        $query = $this->applyConfirmationFilters(
            $this->scopedConfirmationsQuery($request)->with([
                'transportTrip.certificate',
                'transportTrip.originFacility',
                'transportTrip.destinationFacility',
                'receivingFacility',
                'client',
                'exportDocuments',
            ]),
            $request,
            $facilityIds
        );

        $confirmations = $query->latest('received_date')->paginate(10)->withQueryString();

        $kpiQuery = $this->applyConfirmationFilters(
            $this->scopedConfirmationsQuery($request),
            $request,
            $facilityIds
        );

        return view('delivery-confirmations.index', [
            'confirmations' => $confirmations,
            'kpis' => [
                'total' => (clone $kpiQuery)->count(),
                'confirmed' => (clone $kpiQuery)->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)->count(),
            ],
            'facilities' => Facility::whereIn('id', $facilityIds)->orderBy('facility_name')->get(),
            'filters' => $request->only(['confirmation_status', 'client_id', 'from', 'to', 'receiving_facility_id']),
        ]);
    }

    public function export(ExportDeliveryConfirmationsRequest $request): StreamedResponse|JsonResponse|Response
    {
        $facilityIds = $this->accessibleFacilityIds($request);
        $confirmations = $this->applyConfirmationFilters(
            $this->scopedConfirmationsQuery($request)->with([
                'transportTrip.certificate',
                'receivingFacility',
                'client',
                'contract',
                'fulfillingDemand',
                'exportDocuments',
            ]),
            $request,
            $facilityIds
        )->orderByDesc('received_date')->get();

        $format = $request->input('format', 'csv');

        return match ($format) {
            'excel' => $this->streamExcel($confirmations, $this->confirmationCsvColumns(), 'delivery_confirmations'),
            'pdf' => $this->streamTripsListPdf($confirmations, 'delivery-confirmations.export-pdf', [
                'filters' => $request->validated(),
            ]),
            'json' => $this->jsonExport($confirmations),
            default => $this->streamCsv($confirmations, $this->confirmationCsvColumns(), 'delivery-confirmations-'.now()->format('Ymd-His').'.csv'),
        };
    }

    public function contracts(Request $request): JsonResponse
    {
        $clientId = (int) $request->query('client_id');
        if ($clientId <= 0) {
            return response()->json([]);
        }

        if (! $this->accessibleClientIds($request)->contains($clientId)) {
            abort(404);
        }

        $contracts = Contract::query()
            ->where('client_id', $clientId)
            ->whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->orderByDesc('id')
            ->get(['id', 'contract_number', 'title'])
            ->map(fn (Contract $c) => [
                'id' => $c->id,
                'reference' => $c->contract_number,
                'title' => $c->title,
            ]);

        return response()->json($contracts);
    }

    public function create(Request $request): View
    {
        return view('delivery-confirmations.create', array_merge(
            $this->formOptions($request, onlyTripsWithoutConfirmation: true),
            ['preselectedTripId' => $request->integer('transport_trip_id') ?: null]
        ));
    }

    public function store(StoreDeliveryConfirmationRequest $request): RedirectResponse
    {
        if ($redirect = $this->clientActiveRedirect($request)) {
            return $redirect;
        }

        $data = $this->validatedConfirmationData($request);
        $confirmation = DeliveryConfirmation::create($data);

        if (config('processor.auto_link_demand')) {
            $this->maybeAutoLinkDemand($request, $confirmation);
        }

        return redirect()->route('delivery-confirmations.index')
            ->with('status', __('Delivery confirmation recorded successfully.'));
    }

    public function show(Request $request, DeliveryConfirmation $deliveryConfirmation): View
    {
        $this->ensureConfirmationInScope($request, $deliveryConfirmation);
        $deliveryConfirmation->load([
            'transportTrip.certificate.batch',
            'transportTrip.originFacility',
            'transportTrip.destinationFacility',
            'receivingFacility',
            'client',
            'contract',
            'fulfillingDemand',
            'exportDocuments',
        ]);

        return view('delivery-confirmations.show', ['confirmation' => $deliveryConfirmation]);
    }

    public function edit(Request $request, DeliveryConfirmation $deliveryConfirmation): View
    {
        $this->ensureConfirmationInScope($request, $deliveryConfirmation);

        return view('delivery-confirmations.edit', array_merge(
            ['confirmation' => $deliveryConfirmation],
            $this->formOptions($request, onlyTripsWithoutConfirmation: false)
        ));
    }

    public function update(UpdateDeliveryConfirmationRequest $request, DeliveryConfirmation $deliveryConfirmation): RedirectResponse
    {
        $this->ensureConfirmationInScope($request, $deliveryConfirmation);

        if ($redirect = $this->clientActiveRedirect($request)) {
            return $redirect;
        }

        $deliveryConfirmation->update($this->validatedConfirmationData($request));

        return redirect()->route('delivery-confirmations.index')
            ->with('status', __('Delivery confirmation updated successfully.'));
    }

    public function destroy(Request $request, DeliveryConfirmation $deliveryConfirmation): RedirectResponse
    {
        $this->ensureConfirmationInScope($request, $deliveryConfirmation);

        $demand = $deliveryConfirmation->fulfillingDemand;
        if ($demand) {
            $demand->update([
                'fulfilled_by_delivery_id' => null,
                'status' => Demand::STATUS_IN_PROGRESS,
            ]);
        }

        $deliveryConfirmation->delete();

        return redirect()->route('delivery-confirmations.index')
            ->with('status', __('Delivery confirmation removed.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedConfirmationData(Request $request): array
    {
        $tripIds = $this->accessibleTripIds($request);
        $facilityIds = $this->accessibleFacilityIds($request);
        $validated = $request->validated();

        if (! $tripIds->contains((int) $validated['transport_trip_id'])) {
            abort(404);
        }

        $receivingFacilityId = $validated['receiving_facility_id'] ?? null;
        if ($receivingFacilityId !== null && $receivingFacilityId !== '' && ! $facilityIds->contains((int) $receivingFacilityId)) {
            abort(404);
        }

        $clientId = $validated['client_id'] ?? null;
        if ($clientId) {
            $client = Client::find($clientId);
            if (! $client || ! $request->user()->accessibleBusinessIds()->contains($client->business_id)) {
                abort(404);
            }
        }

        $contractId = $validated['contract_id'] ?? null;
        if ($contractId) {
            $contract = Contract::find($contractId);
            if (! $contract || ! $request->user()->accessibleBusinessIds()->contains($contract->business_id)) {
                abort(404);
            }
            if ($clientId && (int) $contract->client_id !== (int) $clientId) {
                abort(404);
            }
        }

        if (empty($validated['received_unit'])) {
            $validated['received_unit'] = ReceivedUnit::Units->value;
        }

        if ($receivingFacilityId === '') {
            $validated['receiving_facility_id'] = null;
        }

        return $validated;
    }

    protected function clientActiveRedirect(Request $request): ?RedirectResponse
    {
        $clientId = $request->validated('client_id');
        if (! $clientId) {
            return null;
        }

        $client = Client::find($clientId);
        if (! $client || ! $request->user()->accessibleBusinessIds()->contains($client->business_id)) {
            abort(404);
        }
        if (! $client->is_active) {
            return redirect()->back()->withInput()->withErrors([
                'client_id' => __('Deliveries can only be created for active customers.'),
            ]);
        }

        return null;
    }

    protected function maybeAutoLinkDemand(Request $request, DeliveryConfirmation $confirmation): void
    {
        if (! $confirmation->client_id && ! $confirmation->receiving_facility_id) {
            return;
        }

        $demand = Demand::query()
            ->whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->whereNull('fulfilled_by_delivery_id')
            ->whereNotIn('status', [Demand::STATUS_FULFILLED, Demand::STATUS_CANCELLED])
            ->where(function ($q) use ($confirmation) {
                $matched = false;
                if ($confirmation->client_id) {
                    $q->where('client_id', $confirmation->client_id);
                    $matched = true;
                }
                if ($confirmation->receiving_facility_id) {
                    $matched ? $q->orWhere('destination_facility_id', $confirmation->receiving_facility_id)
                        : $q->where('destination_facility_id', $confirmation->receiving_facility_id);
                }
            })
            ->latest('id')
            ->first();

        if ($demand) {
            $demand->update([
                'fulfilled_by_delivery_id' => $confirmation->id,
                'status' => Demand::STATUS_FULFILLED,
            ]);
        }
    }

    /**
     * @return array<string, callable>
     */
    protected function confirmationCsvColumns(): array
    {
        return [
            'Confirmation ID' => fn (DeliveryConfirmation $c) => $c->id,
            'Trip ID' => fn (DeliveryConfirmation $c) => $c->transport_trip_id,
            'Certificate number' => fn (DeliveryConfirmation $c) => $c->transportTrip?->certificate?->certificate_number ?? '',
            'Received date' => fn (DeliveryConfirmation $c) => $c->received_date?->format('Y-m-d') ?? '',
            'Received quantity' => fn (DeliveryConfirmation $c) => $c->received_quantity,
            'Received unit' => fn (DeliveryConfirmation $c) => $c->received_unit ?? '',
            'Confirmation status' => fn (DeliveryConfirmation $c) => $c->confirmation_status,
            'Receiver name' => fn (DeliveryConfirmation $c) => $c->receiver_name,
            'Receiving facility' => fn (DeliveryConfirmation $c) => $c->isExternalRecipient()
                ? __('External')
                : ($c->receivingFacility?->facility_name ?? ''),
            'Client' => fn (DeliveryConfirmation $c) => $c->client?->display_name ?? '',
            'Contract' => fn (DeliveryConfirmation $c) => $c->contract?->contract_number ?? '',
            'Receiver country' => fn (DeliveryConfirmation $c) => $c->receiver_country ?? '',
            'Receiver address' => fn (DeliveryConfirmation $c) => $c->receiver_address ?? '',
            'Linked demand ID' => fn (DeliveryConfirmation $c) => $c->fulfillingDemand?->id ?? '',
            'Is international' => fn (DeliveryConfirmation $c) => $c->isInternationalExport() ? 'Yes' : 'No',
            'Vet. health cert. number' => fn (DeliveryConfirmation $c) => $this->exportDoc($c, MeatExportDocumentType::VeterinaryHealthCertificate)->document_number ?? '',
            'Vet. health cert. status' => fn (DeliveryConfirmation $c) => $this->exportDoc($c, MeatExportDocumentType::VeterinaryHealthCertificate)->status ?? '',
            'Customs permit number' => fn (DeliveryConfirmation $c) => $this->exportDoc($c, MeatExportDocumentType::CustomsExportPermit)->document_number ?? '',
            'Customs permit status' => fn (DeliveryConfirmation $c) => $this->exportDoc($c, MeatExportDocumentType::CustomsExportPermit)->status ?? '',
            'Commercial invoice number' => fn (DeliveryConfirmation $c) => $this->exportDoc($c, MeatExportDocumentType::CommercialInvoice)->document_number ?? '',
            'Commercial invoice status' => fn (DeliveryConfirmation $c) => $this->exportDoc($c, MeatExportDocumentType::CommercialInvoice)->status ?? '',
            'Cold chain log number' => fn (DeliveryConfirmation $c) => $this->exportDoc($c, MeatExportDocumentType::ColdChainLog)->document_number ?? '',
            'Cold chain log status' => fn (DeliveryConfirmation $c) => $this->exportDoc($c, MeatExportDocumentType::ColdChainLog)->status ?? '',
            'All documents issued' => fn (DeliveryConfirmation $c) => $c->allExportDocumentsIssued() ? 'Yes' : 'No',
        ];
    }

    protected function exportDoc(DeliveryConfirmation $c, MeatExportDocumentType $type): ?MeatExportDocument
    {
        if (! $c->relationLoaded('exportDocuments')) {
            return null;
        }

        return $c->exportDocuments->firstWhere('document_type', $type->value);
    }

    protected function applyConfirmationFilters(
        \Illuminate\Database\Eloquent\Builder $query,
        Request $request,
        \Illuminate\Support\Collection $facilityIds
    ): \Illuminate\Database\Eloquent\Builder {
        $clientIds = $this->accessibleClientIds($request);

        return $query
            ->when($request->filled('confirmation_status'), fn ($q) => $q->where('confirmation_status', $request->string('confirmation_status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('received_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('received_date', '<=', $request->date('to')))
            ->when(
                $request->filled('receiving_facility_id') && $facilityIds->contains((int) $request->receiving_facility_id),
                fn ($q) => $q->where('receiving_facility_id', $request->integer('receiving_facility_id'))
            )
            ->when(
                $request->filled('client_id') && $clientIds->contains((int) $request->client_id),
                fn ($q) => $q->where('client_id', $request->integer('client_id'))
            );
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(Request $request, bool $onlyTripsWithoutConfirmation): array
    {
        $tripIds = $this->accessibleTripIds($request);
        $tripsQuery = TransportTrip::with('certificate', 'originFacility', 'destinationFacility')
            ->whereIn('id', $tripIds)
            ->latest('departure_date');

        if ($onlyTripsWithoutConfirmation) {
            $tripsQuery->whereDoesntHave('deliveryConfirmation');
        }

        return [
            'trips' => $tripsQuery->get()->map(fn (TransportTrip $t) => [
                'id' => $t->id,
                'label' => $t->vehicle_plate_number.' — '.($t->originFacility->facility_name ?? '').' → '.$t->destination_display.' ('.$t->departure_date->format('d M Y').')',
                'external_destination' => $t->isExternalDestination(),
                'destination_name' => $t->destination_name ?? '',
                'destination_country' => $t->destination_country ?? '',
                'destination_address' => $t->destination_address ?? '',
            ]),
            'facilities' => Facility::whereIn('id', $this->accessibleFacilityIds($request))
                ->orderBy('facility_name')
                ->get()
                ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]),
            'clients' => Client::whereIn('business_id', $request->user()->accessibleBusinessIds())
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Client $c) => [
                    'id' => $c->id,
                    'label' => $c->display_name,
                    'name' => $c->name,
                    'country' => $c->country ?? '',
                    'address' => $c->address_line,
                ]),
            'receivedUnits' => ReceivedUnit::cases(),
            'contractsUrl' => route('delivery-confirmations.contracts'),
        ];
    }
}
