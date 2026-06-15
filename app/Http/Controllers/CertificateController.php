<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCertificateRequest;
use App\Http\Requests\UpdateCertificateRequest;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Support\DomPdf;
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
     * @return array<string, int>
     */
    private function buildCertHubStats(Request $request): array
    {
        $batchIds = $this->userBatchIds($request);

        return [
            'total_issued' => Certificate::whereIn('batch_id', $batchIds)->count(),
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
                ->whereHas('postMortemInspection', fn ($q) => $q->where('approved_quantity', '>', 0))
                ->whereDoesntHave('certificate')
                ->count(),
        ];
    }

    /** Certification module home: prerequisites summary and primary “issue certificate” action. */
    public function hub(Request $request): View
    {
        // --- Section 2 ---
        $batchIds = $this->userBatchIds($request);
        $hubStats = $this->buildCertHubStats($request);

        $byStatus = Certificate::whereIn('batch_id', $batchIds)
            ->with([
                'batch.slaughterExecution.slaughterPlan.facility',
                'inspector',
                'batch.postMortemInspection',
                'transportTrips',
            ])
            ->orderByDesc('issued_at')
            ->get()
            ->groupBy(fn ($c) => $c->isRevoked() ? 'revoked' : ($c->isExpired() ? 'expired' : 'active'));

        $recentCertificates = Certificate::whereIn('batch_id', $batchIds)
            ->with(['batch.slaughterExecution.slaughterPlan.facility', 'inspector'])
            ->orderByDesc('issued_at')
            ->limit(10)
            ->get();

        $readyBatches = Batch::whereIn('id', $batchIds)
            ->whereHas('postMortemInspection', fn ($q) => $q->where('approved_quantity', '>', 0))
            ->whereDoesntHave('certificate')
            ->with(['slaughterExecution.slaughterPlan.facility', 'postMortemInspection'])
            ->limit(10)
            ->get();

        return view('certificates.hub', compact(
            'hubStats',
            'byStatus',
            'recentCertificates',
            'readyBatches',
        ));
    }

    public function index(Request $request): View
    {
        // --- Section 2 ---
        $batchIds = $this->userBatchIds($request);
        $facilityIds = $this->userFacilityIds($request);
        $hubStats = $this->buildCertHubStats($request);

        $certificates = Certificate::query()
            ->with([
                'batch.slaughterExecution.slaughterPlan.facility',
                'inspector',
                'facility',
                'batch.postMortemInspection',
                'transportTrips',
            ])
            ->whereIn('batch_id', $batchIds)
            ->when($request->query('status') === 'active', fn ($q) => $q
                ->where('status', '!=', Certificate::STATUS_REVOKED)
                ->where(fn ($q2) => $q2->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', today())))
            ->when($request->query('status') === 'expired', fn ($q) => $q
                ->where('status', '!=', Certificate::STATUS_REVOKED)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', today()))
            ->when($request->query('status') === 'revoked', fn ($q) => $q
                ->where('status', Certificate::STATUS_REVOKED))
            ->when($request->filled('facility_id'), fn ($q) => $q
                ->where('facility_id', (int) $request->query('facility_id')))
            ->when($request->query('has_transport') === '1', fn ($q) => $q->has('transportTrips'))
            ->when($request->query('has_transport') === '0', fn ($q) => $q->doesntHave('transportTrips'))
            ->when($request->filled('issued_from'), fn ($q) => $q
                ->whereDate('issued_at', '>=', $request->query('issued_from')))
            ->when($request->filled('issued_to'), fn ($q) => $q
                ->whereDate('issued_at', '<=', $request->query('issued_to')))
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $facilities = Facility::query()
            ->whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name']);

        return view('certificates.index', compact('hubStats', 'certificates', 'facilities'));
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

    public function exportSingle(Request $request, Certificate $certificate): Response
    {
        $this->authorizeCertificate($request, $certificate);
        $certificate->load([
            'batch.postMortemInspection',
            'batch.slaughterExecution.slaughterPlan.facility',
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
        ]);

        $fileName = 'certificate-'.($certificate->certificate_number ?: $certificate->id).'.pdf';
        $pdf = DomPdf::loadView('certificates.pdf.single', [
            'certificate' => $certificate,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }

    public function create(Request $request): View
    {
        $batchIds = $this->userBatchIds($request);
        $facilityIds = $this->userFacilityIds($request);

        // Batches that can receive a certificate: have post-mortem with approved_quantity > 0 and no certificate yet
        $batches = Batch::with(['postMortemInspection', 'slaughterExecution.slaughterPlan.facility'])
            ->whereIn('id', $batchIds)
            ->whereHas('postMortemInspection', fn ($q) => $q->where('approved_quantity', '>', 0))
            ->whereDoesntHave('certificate')
            ->latest()
            ->get()
            ->map(fn (Batch $b) => [
                'id' => $b->id,
                'label' => $b->batch_code.' — '.$b->slaughterExecution->slaughterPlan->facility->facility_name.' (approved: '.$b->postMortemInspection->approved_quantity.')',
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

        // --- Section 2 ---
        $selectedBatchId = $request->query('batch_id');
        $selectedBatch = $selectedBatchId
            ? Batch::whereIn('id', $batchIds)
                ->with([
                    'postMortemInspection.inspectionItems.intakeItem',
                    'items.intakeItem',
                    'slaughterExecution.slaughterPlan.facility',
                ])
                ->find($selectedBatchId)
            : null;

        return view('certificates.create', [
            'batches' => $batches,
            'inspectorsByFacility' => $inspectorsByFacility,
            'facilities' => $facilities,
            'selectedBatch' => $selectedBatch,
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
                    'slug' => (string) Str::uuid(),
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

        return view('certificates.edit', [
            'certificate' => $certificate,
            'batches' => $batches,
            'inspectorsByFacility' => $inspectorsByFacility,
            'facilities' => $facilities,
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

        return redirect()->route('certificates.index')
            ->with('status', __('Certificate updated successfully.'));
    }

    public function destroy(Request $request, Certificate $certificate): RedirectResponse
    {
        $this->authorizeCertificate($request, $certificate);
        $certificate->delete();

        return redirect()->route('certificates.index')
            ->with('status', __('Certificate removed.'));
    }
}
