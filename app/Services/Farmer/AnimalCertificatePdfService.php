<?php

namespace App\Services\Farmer;

use App\Models\AnimalCertificate;
use App\Support\DomPdf;
use App\Support\PdfQrCode;
use Illuminate\Support\Facades\Storage;

class AnimalCertificatePdfService
{
    public function __construct(
        private readonly AnimalCertificateTraceabilityService $traceability,
    ) {}

    public function generate(AnimalCertificate $certificate): string
    {
        $certificate->load(['animal.livestock.farm.business', 'template']);
        $summary = $this->traceability->summarize($certificate->animal);
        $qrImage = PdfQrCode::dataUri($certificate->verificationUrl());

        $pdf = DomPdf::loadView('farmer.animal-certificates.pdf', [
            'certificate' => $certificate,
            'summary' => $summary,
            'qrImage' => $qrImage,
        ])->setPaper('a4', 'portrait');

        $path = 'animal-certificates/'.$certificate->certificate_number.'.pdf';
        Storage::disk('public')->put($path, $pdf->output());
        $certificate->update(['pdf_path' => $path]);

        return $path;
    }
}
