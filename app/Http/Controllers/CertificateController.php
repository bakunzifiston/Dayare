<?php

namespace App\Http\Controllers;

use App\Exceptions\CertificatePdfException;
use App\Http\Requests\StoreCertificateRequest;
use App\Http\Requests\UpdateCertificateRequest;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\WarehouseStorage;
use App\Services\Processor\CertificatePdfService;
use App\Support\DomPdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateController extends Controller
{
    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCertificateFilters($query, array $filters)
    {
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['facility_id'])) {
            $query->where('facility_id', (int) $filters['facility_id']);
        }

        if (! empty($filters['issued_from'])) {
            $query->whereDate('issued_at', '>=', (string) $filters['issued_from']);
        }

        if (! empty($filters['issued_to'])) {
            $query->whereDate('issued_at', '<=', (string) $filters['issued_to']);
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int|string>  $batchIds
     * @param  \Illuminate\Support\Collection<int, int|string>  $facilityIds
     */
    private function scopedCertificatesQuery($batchIds, $facilityIds)
    {
        return Certificate::query()->where(function ($q) use ($batchIds, $facilityIds) {
            $q->whereIn('batch_id', $batchIds)
                ->orWhere(function ($q2) use ($facilityIds) {
                    $q2->whereNull('batch_id')->whereIn('facility_id', $facilityIds);
                });
        });
    }

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

    private function authorizeCertificate(Request $request, Certificate $certificate): void
    {
        if ($certificate->batch_id && ! $this->userBatchIds($request)->contains($certificate->batch_id)) {
            abort(404);
        }
        if ($certificate->batch_id === null && ! $this->userFacilityIds($request)->contains($certificate->facility_id)) {
            abort(404);
        }
    }

    // --- Section 2 ---

    /**
     * @param  \Illuminate\Support\Collection<int, int|string>  $batchIds
     * @return array<string, int|string>
     */
    private function buildCertHubStats($batchIds, array $filters): array
    {
        $scopeCertificates = function ($query) use ($batchIds, $filters): void {
            $query->whereIn('batch_id', $batchIds);
            if ($filters['is_filtered']) {
                $query->whereDate('issued_at', '>=', $filters['start']->toDateString())
                    ->whereDate('issued_at', '<=', $filters['end']->toDateString());
            }
        };

        return [
            'certificates_label' => $filters['certificates_label'],
            'total_issued' => Certificate::query()
                ->where($scopeCertificates)
                ->count(),
            'active' => Certificate::whereIn('batch_id', $batchIds)
                ->where('status', '!=', Certificate::STATUS_REVOKED)
                ->where(fn ($q) => $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', today()))
                ->count(),
            'expired' => Certificate::whereIn('batch_id', $batchIds)
                ->where('status', '!=', Certificate::STATUS_REVOKED)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', today())
                ->count(),
            'revoked' => Certificate::whereIn('batch_id', $batchIds)
                ->where('status', Certificate::STATUS_REVOKED)
                ->count(),
            'ready_to_issue' => Batch::whereIn('id', $batchIds)
                ->eligibleForCertificate()
                ->count(),
        ];
    }

    public function hub(Request $request): View
    {
        $batchIds = $this->userBatchIds($request);
        $filters = $this->resolveHubFilters($request);

        $scopeCertificates = function ($query) use ($batchIds, $filters): void {
            $query->whereIn('batch_id', $batchIds);
            if ($filters['is_filtered']) {
                $query->whereDate('issued_at', '>=', $filters['start']->toDateString())
                    ->whereDate('issued_at', '<=', $filters['end']->toDateString());
            }
        };

        $hubStats = $this->buildCertHubStats($batchIds, $filters);

        $certificates = Certificate::query()
            ->where($scopeCertificates)
            ->with([
                'batch.slaughterExecution.slaughterPlan.facility',
                'inspector',
                'facility',
                'batch.postMortemInspection',
                'transportTrips',
            ])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $readyBatches = Batch::whereIn('id', $batchIds)
            ->eligibleForCertificate()
            ->with(['slaughterExecution.slaughterPlan.facility', 'postMortemInspection'])
            ->limit(10)
            ->get();

        return view('certificates.hub', compact(
            'hubStats',
            'certificates',
            'readyBatches',
            'filters',
        ));
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('certificates.hub', $request->query());
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: null,
     *     end: null,
     *     range_label: string,
     *     certificates_label: string,
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
            'certificates_label' => __('Total issued'),
            'has_custom_range' => false,
            'is_filtered' => false,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, date_from: string, date_to: string, range_label: string, certificates_label: string}
     */
    private function presetRangeForPeriod(string $period): array
    {
        $now = now();

        [$start, $end, $rangeLabel, $certificatesLabel] = match ($period) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->format('M j, Y'),
                __('Issued today'),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (string) $now->year,
                __('Issued this year'),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->format('F Y'),
                __('Issued this month'),
            ],
            default => throw new \InvalidArgumentException('Invalid preset period.'),
        };

        return [
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'range_label' => $rangeLabel,
            'certificates_label' => $certificatesLabel,
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
     *     certificates_label: string,
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
                'certificates_label' => __('Issued in range'),
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
                'certificates_label' => $preset['certificates_label'],
                'has_custom_range' => false,
                'is_filtered' => true,
            ];
        }

        return $this->hubFiltersAllTime();
    }

    public function export(Request $request): Response
    {
        $batchIds = $this->userBatchIds($request);
        $facilityIds = $this->userFacilityIds($request);
        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'facility_id' => (string) $request->query('facility_id', ''),
            'issued_from' => (string) $request->query('issued_from', ''),
            'issued_to' => (string) $request->query('issued_to', ''),
        ];

        $rows = $this->applyCertificateFilters(
            $this->scopedCertificatesQuery($batchIds, $facilityIds)
                ->with(['batch', 'inspector', 'facility']),
            $filters
        )
            ->latest('issued_at')
            ->get();

        $fileName = 'certificates-'.now()->format('Ymd-His').'.pdf';
        $pdf = DomPdf::loadView('certificates.pdf.list', [
            'rows' => $rows,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    public function exportSingle(Request $request, Certificate $certificate, CertificatePdfService $certificatePdfService): Response|RedirectResponse
    {
        $this->authorizeCertificate($request, $certificate);

        try {
            $pdf = $certificatePdfService->generate($certificate);
            $fileName = $certificatePdfService->downloadFilename($certificate);
        } catch (CertificatePdfException $e) {
            return redirect()
                ->back()
                ->withErrors(['certificate_pdf' => $e->getMessage()]);
        }

        return $pdf->download($fileName);
    }

    public function create(Request $request): View
    {
        $batchIds = $this->userBatchIds($request);
        $facilityIds = $this->userFacilityIds($request);
        $releasedBatchIds = WarehouseStorage::releasedBatchIdsFor($batchIds);

        $batches = Batch::with(['postMortemInspection.inspectionItems', 'slaughterExecution.slaughterPlan.facility', 'inspector'])
            ->whereIn('id', $releasedBatchIds)
            ->whereDoesntHave('certificate')
            ->latest()
            ->get()
            ->filter(fn (Batch $b) => $b->canIssueCertificate())
            ->map(function (Batch $b) {
                $facilityName = $b->slaughterExecution?->slaughterPlan?->facility?->facility_name ?? __('Unknown facility');
                $approvedLabel = $b->postMortemInspection?->approved_quantity
                    ?? ($b->hasReleasedStorageWithPostMortemItem() ? __('released from cold room') : '—');

                return [
                    'id' => $b->id,
                    'label' => $b->batch_code.' — '.$facilityName.' ('.__('approved').': '.$approvedLabel.')',
                    'facility_id' => $b->slaughterExecution?->slaughterPlan?->facility_id,
                    'inspector_id' => $b->inspector_id,
                ];
            })
            ->values();

        $blockedBatches = Batch::with(['postMortemInspection.inspectionItems', 'slaughterExecution.slaughterPlan.facility', 'warehouseStorages'])
            ->whereIn('id', $releasedBatchIds)
            ->whereDoesntHave('certificate')
            ->latest()
            ->get()
            ->reject(fn (Batch $b) => $b->canIssueCertificate())
            ->map(fn (Batch $b) => [
                'batch_code' => $b->batch_code,
                'reason' => $b->certificateIssueBlockReason() ?? __('Not eligible for certification.'),
            ])
            ->values();

        $pendingColdRoomRelease = WarehouseStorage::query()
            ->forColdRoomUser($request)
            ->where('status', WarehouseStorage::STATUS_IN_STORAGE)
            ->with(['batch', 'intakeItem'])
            ->whereIn('batch_id', $batchIds)
            ->whereHas('batch', fn ($q) => $q->whereDoesntHave('certificate'))
            ->latest('entry_date')
            ->get()
            ->map(fn (WarehouseStorage $storage) => [
                'id' => $storage->id,
                'batch_code' => $storage->batch?->batch_code ?? '—',
                'ear_tag' => $storage->intakeItem?->ear_tag,
                'quantity' => $storage->quantity_stored,
                'unit' => $storage->quantity_unit_label,
                'edit_url' => route('warehouse-storages.edit', $storage),
            ])
            ->values();

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);

        // --- Section 2 ---
        $selectedBatchId = $request->query('batch_id');
        $selectedBatch = $selectedBatchId
            ? Batch::whereIn('id', $batchIds)
                ->with([
                    'postMortemInspection.inspectionItems.intakeItem',
                    'items.intakeItem',
                    'slaughterExecution.slaughterPlan.facility',
                    'warehouseStorages',
                ])
                ->find($selectedBatchId)
            : null;

        $defaultInspectorId = old('inspector_id', $selectedBatch?->canIssueCertificate() ? $selectedBatch->inspector_id : null);
        $defaultFacilityId = old(
            'facility_id',
            $selectedBatch?->canIssueCertificate()
                ? $selectedBatch->slaughterExecution?->slaughterPlan?->facility_id
                : null,
        );

        $pdfDefaults = [];
        if ($selectedBatch?->canIssueCertificate()) {
            $pdfDefaults = app(CertificatePdfService::class)->suggestedPdfDetails(
                $selectedBatch,
                $selectedBatch->slaughterExecution?->slaughterPlan?->facility,
            );
        }

        return view('certificates.create', [
            'batches' => $batches,
            'blockedBatches' => $blockedBatches,
            'pendingColdRoomRelease' => $pendingColdRoomRelease,
            'inspectorsByFacility' => $inspectorsByFacility,
            'facilities' => $facilities,
            'selectedBatch' => $selectedBatch,
            'defaultInspectorId' => $defaultInspectorId,
            'defaultFacilityId' => $defaultFacilityId,
            'defaultSlaughterhouseName' => CertificatePdfService::NYAGATARE_FACILITY_NAME,
            'pdfDefaults' => $pdfDefaults,
            'savedPdfDetails' => [],
        ]);
    }

    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        $batchIds = $this->userBatchIds($request);
        if (! $batchIds->contains((int) $request->validated('batch_id'))) {
            abort(404);
        }

        $certificate = DB::transaction(function () use ($request) {
            $certificate = Certificate::create($request->validated());

            // --- Section 2 --- Auto-create QR so every certificate is immediately traceable
            if (! $certificate->certificateQr) {
                $certificate->certificateQr()->create([
                    'slug' => CertificateQr::generateSlug(),
                ]);
            }

            return $certificate;
        });

        return redirect()->route('certificates.hub')
            ->with('status', __('Certificate :number issued.', ['number' => $certificate->certificate_number ?: $certificate->id]));
    }

    public function show(Request $request, Certificate $certificate): View|RedirectResponse
    {
        $this->authorizeCertificate($request, $certificate);

        // --- Section 2 ---
        $certificate->load([
            'batch.items.intakeItem',
            'batch.items.postMortemOutcome',
            'batch.postMortemInspection.inspectionItems.intakeItem',
            'batch.slaughterExecution.slaughterPlan.facility',
            'batch.slaughterExecution.slaughterPlan.intake',
            'batch.slaughterExecution.slaughterPlan.anteMortemInspections',
            'batch.slaughterExecution.slaughterPlan.animalIntake.country',
            'batch.slaughterExecution.slaughterPlan.animalIntake.province',
            'batch.slaughterExecution.slaughterPlan.animalIntake.district',
            'batch.slaughterExecution.slaughterPlan.animalIntake.sector',
            'batch.slaughterExecution.slaughterPlan.animalIntake.cell',
            'batch.slaughterExecution.slaughterPlan.animalIntake.village',
            'inspector',
            'facility',
            'certificateQr',
            'transportTrips.originFacility',
            'transportTrips.destinationFacility',
            'warehouseStorages',
        ]);
        if (! $certificate->certificateQr) {
            $certificate->certificateQr()->create(['slug' => CertificateQr::generateSlug()]);
            $certificate->load('certificateQr');
        }

        return view('certificates.show', ['certificate' => $certificate]);
    }

    public function qr(Request $request, Certificate $certificate): Response
    {
        $this->authorizeCertificate($request, $certificate);
        $qr = $certificate->certificateQr ?? $certificate->certificateQr()->create(['slug' => CertificateQr::generateSlug()]);
        $url = $qr->trace_url;

        $svg = QrCode::format('svg')->size(200)->margin(1)->generate($url);

        return response((string) $svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function edit(Request $request, Certificate $certificate): View|RedirectResponse
    {
        $this->authorizeCertificate($request, $certificate);

        $batchIds = $this->userBatchIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $batches = Batch::with(['postMortemInspection', 'slaughterExecution.slaughterPlan.facility'])
            ->whereIn('id', $batchIds)
            ->whereHas('postMortemInspection', fn ($q) => $q->where('approved_quantity', '>', 0))
            ->latest()
            ->get()
            ->map(fn (Batch $b) => [
                'id' => $b->id,
                'label' => $b->batch_code.' — '.$b->slaughterExecution->slaughterPlan->facility->facility_name,
                'facility_id' => $b->slaughterExecution->slaughterPlan->facility_id,
            ]);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);

        $certificate->load(['batch', 'facility', 'transportTrips']);
        $pdfDefaults = app(CertificatePdfService::class)->suggestedPdfDetails(
            $certificate->batch,
            $certificate->facility,
            $certificate->transportTrips->sortByDesc('departure_date')->first(),
        );

        return view('certificates.edit', [
            'certificate' => $certificate,
            'batches' => $batches,
            'inspectorsByFacility' => $inspectorsByFacility,
            'facilities' => $facilities,
            'pdfDefaults' => $pdfDefaults,
            'savedPdfDetails' => $certificate->pdf_details ?? [],
        ]);
    }

    public function update(UpdateCertificateRequest $request, Certificate $certificate): RedirectResponse
    {
        $this->authorizeCertificate($request, $certificate);
        $batchIds = $this->userBatchIds($request);
        if (! $batchIds->contains((int) $request->validated('batch_id'))) {
            abort(404);
        }

        $certificate->update($request->validated());

        return redirect()->route('certificates.hub')
            ->with('status', __('Certificate updated successfully.'));
    }

    public function destroy(Request $request, Certificate $certificate): RedirectResponse
    {
        $this->authorizeCertificate($request, $certificate);
        $certificate->delete();

        return redirect()->route('certificates.hub')
            ->with('status', __('Certificate removed.'));
    }
}
