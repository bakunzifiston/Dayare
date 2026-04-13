<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreFarmerHealthCertificateRequest;
use App\Models\AnimalHealthRecord;
use App\Models\Farm;
use App\Models\FarmerHealthCertificate;
use App\Models\Livestock;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FarmerHealthCertificateController extends Controller
{
    public function index(Request $request): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $certificates = FarmerHealthCertificate::query()
            ->whereIn('farmer_id', $farmerIds)
            ->with(['farm', 'livestock', 'sourceHealthRecord'])
            ->latest('issue_date')
            ->paginate(20);

        return view('farmer.health-certificates.index', compact('certificates'));
    }

    public function create(Request $request): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $farms = Farm::query()
            ->whereIn('business_id', $farmerIds)
            ->with(['livestock' => fn ($q) => $q->orderBy('type')->orderBy('id')])
            ->orderBy('name')
            ->get();

        $healthRecord = null;
        if ($request->filled('health_record_id')) {
            $healthRecord = AnimalHealthRecord::query()
                ->whereKey((int) $request->integer('health_record_id'))
                ->whereIn('farm_id', $farms->pluck('id'))
                ->with('livestock')
                ->first();
        }

        return view('farmer.health-certificates.create', compact('farms', 'healthRecord'));
    }

    public function store(StoreFarmerHealthCertificateRequest $request): RedirectResponse
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $farm = Farm::query()
            ->whereKey((int) $request->validated('farm_id'))
            ->whereIn('business_id', $farmerIds)
            ->firstOrFail();

        $data = $request->validated();

        if (! empty($data['livestock_id'])) {
            $livestockBelongs = Livestock::query()
                ->whereKey((int) $data['livestock_id'])
                ->where('farm_id', $farm->id)
                ->exists();

            if (! $livestockBelongs) {
                throw ValidationException::withMessages([
                    'livestock_id' => [__('Selected livestock does not belong to the chosen farm.')],
                ]);
            }
        }

        if (! empty($data['source_health_record_id'])) {
            $recordBelongs = AnimalHealthRecord::query()
                ->whereKey((int) $data['source_health_record_id'])
                ->where('farm_id', $farm->id)
                ->exists();

            if (! $recordBelongs) {
                throw ValidationException::withMessages([
                    'source_health_record_id' => [__('Selected health record does not belong to the chosen farm.')],
                ]);
            }
        }

        $filePath = $request->file('file')->store('farmer-health-certificates', 'public');

        $certificate = FarmerHealthCertificate::query()->create([
            'certificate_number' => $data['certificate_number'],
            'farmer_id' => $farm->business_id,
            'farm_id' => $farm->id,
            'livestock_id' => $data['livestock_id'] ?? null,
            'batch_reference' => $data['batch_reference'] ?? null,
            'source_health_record_id' => $data['source_health_record_id'] ?? null,
            'certificate_type' => $data['certificate_type'],
            'issued_by' => $data['issued_by'],
            'issue_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'file_path' => $filePath,
        ]);

        return redirect()
            ->route('farmer.health-certificates.show', $certificate)
            ->with('status', __('Health certificate saved successfully.'));
    }

    public function show(Request $request, FarmerHealthCertificate $healthCertificate): View
    {
        $this->authorizeCertificate($request, $healthCertificate);
        $healthCertificate->load(['farm', 'livestock', 'sourceHealthRecord']);
        $isValidToday = $healthCertificate->isValidOn(Carbon::today());

        return view('farmer.health-certificates.show', compact('healthCertificate', 'isValidToday'));
    }

    public function download(Request $request, FarmerHealthCertificate $healthCertificate)
    {
        $this->authorizeCertificate($request, $healthCertificate);
        abort_unless(Storage::disk('public')->exists($healthCertificate->file_path), 404);

        return Storage::disk('public')->download($healthCertificate->file_path);
    }

    private function authorizeCertificate(Request $request, FarmerHealthCertificate $certificate): void
    {
        $farmerIds = collect($request->user()->accessibleFarmerBusinessIds());

        abort_unless(
            $farmerIds->contains((int) $certificate->farmer_id),
            403
        );
    }
}

