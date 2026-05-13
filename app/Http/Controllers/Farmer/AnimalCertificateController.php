<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Http\Requests\Farmer\StoreAnimalCertificateRequest;
use App\Http\Requests\Farmer\UpdateAnimalCertificateRequest;
use App\Models\AnimalCertificate;
use App\Models\AnimalCertificateLog;
use App\Services\Farmer\AnimalCertificatePdfService;
use App\Services\Farmer\AnimalCertificateService;
use App\Services\Farmer\AnimalCertificateTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnimalCertificateController extends Controller
{
    use InteractsWithAccessibleAnimals;
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AnimalCertificate::class);

        $animalIds = $this->accessibleAnimalIds($request);
        $query = AnimalCertificate::query()->whereIn('animal_id', $animalIds)->with('animal')->latest('issue_date');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('certificate_number', 'like', '%'.$search.'%')
                    ->orWhere('verification_token', 'like', '%'.$search.'%');
            });
        }

        foreach (['certificate_type', 'certificate_status'] as $filter) {
            if ($value = (string) $request->query($filter, '')) {
                $query->where($filter, $value);
            }
        }

        $records = $query->paginate(20)->withQueryString();

        return view('farmer.animal-certificates.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AnimalCertificate::class);

        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();
        $templates = \App\Models\AnimalCertificateTemplate::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request))
            ->where('status', 'active')
            ->orderBy('template_name')
            ->get();

        return view('farmer.animal-certificates.create', [
            'animals' => $animals,
            'templates' => $templates,
            'selectedAnimalId' => (int) $request->query('animal_id'),
        ]);
    }

    public function store(StoreAnimalCertificateRequest $request, AnimalCertificateService $service): RedirectResponse
    {
        $this->authorize('create', AnimalCertificate::class);

        $animal = $this->findAccessibleAnimal($request, (int) $request->validated('animal_id'));
        abort_unless($animal, 404);

        $businessId = (int) $animal->livestock?->farm?->business_id;
        $type = $request->validated('certificate_type');
        $template = $service->resolveTemplate($request->validated('template_id'), $businessId, $type);

        $certificate = AnimalCertificate::query()->create([
            'animal_id' => $animal->id,
            'template_id' => $template?->id,
            'certificate_number' => $service->generateCertificateNumber(),
            'certificate_type' => $type,
            'certificate_title' => $request->validated('certificate_title') ?: ($template?->title_template ?: $service->titleForType($type)),
            'issue_date' => $request->validated('issue_date'),
            'expiry_date' => $request->validated('expiry_date'),
            'issued_by' => $request->validated('issued_by'),
            'veterinarian_name' => $request->validated('veterinarian_name'),
            'verification_token' => $service->generateVerificationToken(),
            'certificate_status' => AnimalCertificate::STATUS_DRAFT,
            'remarks' => $request->validated('remarks'),
            'created_by' => $request->user()->id,
        ]);

        if ($request->boolean('activate', true)) {
            $service->issue($certificate, $request->user()->id);
        } else {
            $service->log($certificate, AnimalCertificateLog::ACTION_CREATED, $request->user()->id);
        }

        return redirect()->route('farmer.certificates.animal-certificates.show', $certificate)
            ->with('status', __('Certificate created.'));
    }

    public function show(AnimalCertificate $certificate, AnimalCertificateTraceabilityService $traceability): View
    {
        $this->authorize('view', $certificate);
        $certificate->load(['animal.livestock.farm', 'template', 'logs.actor']);
        $summary = $traceability->summarize($certificate->animal);

        return view('farmer.animal-certificates.show', compact('certificate', 'summary'));
    }

    public function edit(Request $request, AnimalCertificate $certificate): View
    {
        $this->authorize('update', $certificate);

        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();
        $templates = \App\Models\AnimalCertificateTemplate::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request))
            ->where('status', 'active')
            ->orderBy('template_name')
            ->get();

        return view('farmer.animal-certificates.edit', compact('certificate', 'animals', 'templates'));
    }

    public function update(UpdateAnimalCertificateRequest $request, AnimalCertificate $certificate, AnimalCertificateService $service): RedirectResponse
    {
        $this->authorize('update', $certificate);

        $certificate->update($request->validated());
        $service->log($certificate, AnimalCertificateLog::ACTION_UPDATED, $request->user()->id);

        return redirect()->route('farmer.certificates.animal-certificates.show', $certificate)
            ->with('status', __('Certificate updated.'));
    }

    public function destroy(AnimalCertificate $certificate, AnimalCertificateService $service): RedirectResponse
    {
        $this->authorize('delete', $certificate);
        $service->log($certificate, AnimalCertificateLog::ACTION_REVOKED, auth()->id(), null, __('Archived.'));
        $certificate->delete();

        return redirect()->route('farmer.certificates.animal-certificates.index')->with('status', __('Certificate archived.'));
    }

    public function revoke(Request $request, AnimalCertificate $certificate, AnimalCertificateService $service): RedirectResponse
    {
        $this->authorize('update', $certificate);
        $certificate->update(['certificate_status' => AnimalCertificate::STATUS_REVOKED]);
        $service->log($certificate, AnimalCertificateLog::ACTION_REVOKED, $request->user()->id);

        return back()->with('status', __('Certificate revoked.'));
    }

    public function download(Request $request, AnimalCertificate $certificate, AnimalCertificatePdfService $pdfService, AnimalCertificateService $service): StreamedResponse
    {
        $this->authorize('view', $certificate);

        if (! $certificate->pdf_path || ! Storage::disk('public')->exists($certificate->pdf_path)) {
            $pdfService->generate($certificate);
            $certificate->refresh();
        }

        $service->log($certificate, AnimalCertificateLog::ACTION_DOWNLOADED, $request->user()->id, $request->ip());

        return Storage::disk('public')->download($certificate->pdf_path, $certificate->certificate_number.'.pdf');
    }

    public function qr(AnimalCertificate $certificate): Response
    {
        $this->authorize('view', $certificate);
        $svg = QrCode::format('svg')->size(220)->margin(1)->generate($certificate->verificationUrl());

        return response((string) $svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
