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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateController extends Controller
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

    private function authorizeCertificate(Request $request, Certificate $certificate): void
    {
        if ($certificate->batch_id && ! $this->userBatchIds($request)->contains($certificate->batch_id)) {
            abort(404);
        }
        if ($certificate->batch_id === null && ! $this->userFacilityIds($request)->contains($certificate->facility_id)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $batchIds = $this->userBatchIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $certificates = Certificate::with(['batch.slaughterExecution.slaughterPlan.facility', 'inspector', 'facility'])
            ->where(function ($q) use ($batchIds, $facilityIds) {
                $q->whereIn('batch_id', $batchIds)
                    ->orWhere(function ($q2) use ($facilityIds) {
                        $q2->whereNull('batch_id')->whereIn('facility_id', $facilityIds);
                    });
            })
            ->latest('issued_at')
            ->paginate(10);

        return view('certificates.index', compact('certificates'));
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
                'label' => $b->batch_code . ' — ' . $b->slaughterExecution->slaughterPlan->facility->facility_name . ' (approved: ' . $b->postMortemInspection->approved_quantity . ')',
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

        return view('certificates.create', [
            'batches' => $batches,
            'inspectorsByFacility' => $inspectorsByFacility,
            'facilities' => $facilities,
        ]);
    }

    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        $batchIds = $this->userBatchIds($request);
        if (! $batchIds->contains((int) $request->validated('batch_id'))) {
            abort(404);
        }

        Certificate::create($request->validated());

        return redirect()->route('certificates.index')
            ->with('status', __('Certificate issued successfully.'));
    }

    public function show(Request $request, Certificate $certificate): View|RedirectResponse
    {
        $this->authorizeCertificate($request, $certificate);
        $certificate->load(['batch.slaughterExecution.slaughterPlan.facility', 'inspector', 'facility', 'certificateQr', 'transportTrips.originFacility', 'transportTrips.destinationFacility']);
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
                'label' => $b->batch_code . ' — ' . $b->slaughterExecution->slaughterPlan->facility->facility_name,
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
