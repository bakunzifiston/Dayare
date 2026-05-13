<?php

namespace App\Http\Controllers;

use App\Models\AnimalCertificateLog;
use App\Services\Farmer\AnimalCertificateService;
use App\Services\Farmer\AnimalCertificateTraceabilityService;
use App\Services\PublicAnimalIdentifierResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicAnimalVerificationController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        PublicAnimalIdentifierResolver $resolver,
        AnimalCertificateTraceabilityService $traceability,
        AnimalCertificateService $certificateService,
    ): View {
        $resolved = $resolver->resolve($token);
        abort_if($resolved === null, 404);

        $certificate = $resolved['certificate'];
        $animal = $resolved['animal'];

        if ($certificate !== null) {
            $certificate->syncStatusFromDates();
            $certificateService->log(
                $certificate,
                AnimalCertificateLog::ACTION_VERIFIED,
                null,
                $request->ip(),
                __('Public verification'),
            );
        }

        $summary = $traceability->summarize($animal);

        return view('public.animal-verify', [
            'certificate' => $certificate,
            'animal' => $animal,
            'summary' => $summary,
            'verifiedAt' => now(),
        ]);
    }
}
