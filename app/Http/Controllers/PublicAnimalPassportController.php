<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicAnimalPassportPdfRequest;
use App\Services\Farmer\AnimalCertificateTraceabilityService;
use App\Services\PublicAnimalIdentifierResolver;
use App\Support\DomPdf;
use App\Support\PdfQrCode;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicAnimalPassportController extends Controller
{
    /** Maximum rows per health category on the public passport PDF (newest first). */
    private const PASSPORT_HEALTH_RECORD_LIMIT = 200;

    public function create(): View
    {
        return view('public.animal-passport-form');
    }

    public function pdf(
        PublicAnimalPassportPdfRequest $request,
        PublicAnimalIdentifierResolver $resolver,
        AnimalCertificateTraceabilityService $traceability,
    ): Response {
        $resolved = $resolver->resolve($request->identifier());
        if ($resolved === null) {
            return redirect()
                ->route('animal.passport.lookup')
                ->withInput()
                ->withErrors(['identifier' => __('No animal was found for that tag or code.')]);
        }

        $animal = $resolved['animal'];
        $certificate = $resolved['certificate'];

        $summary = $traceability->summarize($animal);

        $limit = self::PASSPORT_HEALTH_RECORD_LIMIT;

        $vaccinations = $animal->vaccinations()->orderByDesc('vaccination_date')->limit($limit)->get();
        $treatments = $animal->treatments()->orderByDesc('treatment_start_date')->limit($limit)->get();
        $diseaseRecords = $animal->diseaseRecords()->orderByDesc('diagnosis_date')->limit($limit)->get();
        $veterinaryVisits = $animal->veterinaryVisits()->orderByDesc('visit_date')->limit($limit)->get();

        $verifyUrl = $animal->publicVerificationUrl()
            ?? route('animal.verify', ['token' => $animal->animal_code]);

        $qrImage = PdfQrCode::dataUri($verifyUrl);

        $pdf = DomPdf::loadView('public.animal-passport-pdf', [
            'animal' => $animal,
            'certificate' => $certificate,
            'summary' => $summary,
            'vaccinations' => $vaccinations,
            'treatments' => $treatments,
            'diseaseRecords' => $diseaseRecords,
            'veterinaryVisits' => $veterinaryVisits,
            'healthRecordLimit' => $limit,
            'qrImage' => $qrImage,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $animal->displayIdentifier()) ?: 'animal';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safeName.'.pdf"',
        ]);
    }
}
