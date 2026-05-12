<?php

namespace App\Services\Farmer;

use App\Models\AnimalCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AnimalCertificatePdfService
{
    public function __construct(
        private readonly AnimalCertificateTraceabilityService $traceability,
    ) {}

    public function generate(AnimalCertificate $certificate): string
    {
        $certificate->load(['animal.livestock.farm.business', 'template']);
        $summary = $this->traceability->summarize($certificate->animal);
        $qrImage = base64_encode((string) QrCode::format('png')->size(140)->margin(1)->generate($certificate->verificationUrl()));

        $pdf = Pdf::loadView('farmer.animal-certificates.pdf', [
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
