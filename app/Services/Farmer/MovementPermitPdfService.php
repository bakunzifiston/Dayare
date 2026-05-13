<?php

namespace App\Services\Farmer;

use App\Models\MovementPermit;
use App\Support\PdfQrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class MovementPermitPdfService
{
    public function generate(MovementPermit $permit): string
    {
        $permit->load(['sourceFarm.business', 'animals.animal', 'animals.livestock', 'transport', 'veterinaryApproval']);
        $qrImage = PdfQrCode::dataUri($permit->verificationUrl() ?? $permit->permit_number);

        $pdf = Pdf::loadView('farmer.movement.permits.pdf', [
            'permit' => $permit,
            'qrImage' => $qrImage,
        ])->setPaper('a4', 'portrait');

        $path = 'movement-permits/'.$permit->permit_number.'.pdf';
        Storage::disk('public')->put($path, $pdf->output());
        $permit->update(['pdf_path' => $path, 'file_path' => $permit->file_path ?: $path]);

        return $path;
    }
}
