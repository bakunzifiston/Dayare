<?php

namespace App\Services\Farmer;

use App\Models\Sale;
use App\Models\SaleDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SalePdfService
{
    public function __construct(
        private readonly AnimalCertificateTraceabilityService $traceability,
    ) {}

    public function generate(Sale $sale, SaleDocument $document): string
    {
        $summaries = [];
        foreach ($sale->saleAnimals as $line) {
            if ($line->animal) {
                $summaries[] = $this->traceability->summarize($line->animal);
            }
        }

        $verificationUrl = route('farmer.sales.records.show', $sale);
        $qrImage = base64_encode((string) QrCode::format('png')->size(140)->margin(1)->generate($verificationUrl));

        $pdf = Pdf::loadView('farmer.sales.documents.pdf', [
            'sale' => $sale,
            'document' => $document,
            'summaries' => $summaries,
            'qrImage' => $qrImage,
        ])->setPaper('a4', 'portrait');

        $path = 'sales/'.$sale->sale_number.'/'.$document->document_number.'.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}
