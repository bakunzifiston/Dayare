<?php

namespace App\Services\Farmer;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Support\DomPdf;
use App\Support\PdfQrCode;
use Illuminate\Support\Facades\Storage;

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
        $qrImage = PdfQrCode::dataUri($verificationUrl);

        $pdf = DomPdf::loadView('farmer.sales.documents.pdf', [
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
