<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalCertificate;
use App\Models\AnimalCertificateLog;
use App\Services\Farmer\AnimalCertificateService;
use App\Services\Farmer\AnimalCertificateTraceabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicAnimalVerificationController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        AnimalCertificateTraceabilityService $traceability,
        AnimalCertificateService $certificateService,
    ): View {
        $certificate = AnimalCertificate::query()
            ->where(function ($query) use ($token): void {
                $query->where('verification_token', $token)
                    ->orWhere('certificate_number', $token);
            })
            ->with(['animal.livestock.farm.business'])
            ->first();

        $animal = null;
        if (! $certificate) {
            $animal = Animal::query()
                ->where('public_verification_token', $token)
                ->orWhere('animal_code', $token)
                ->orWhere('tag_number', $token)
                ->with(['livestock.farm.business'])
                ->firstOrFail();
        } else {
            $animal = $certificate->animal;
            $certificate->syncStatusFromDates();
            $certificateService->log(
                $certificate,
                AnimalCertificateLog::ACTION_VERIFIED,
                null,
                $request->ip(),
                __('Public verification'),
            );
        }

        abort_unless($animal, 404);

        $summary = $traceability->summarize($animal);

        return view('public.animal-verify', [
            'certificate' => $certificate,
            'animal' => $animal,
            'summary' => $summary,
            'verifiedAt' => now(),
        ]);
    }
}
