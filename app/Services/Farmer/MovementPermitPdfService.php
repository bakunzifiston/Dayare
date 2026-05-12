<?php

namespace App\Services\Farmer;

use App\Models\MovementPermit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MovementPermitPdfService
{
    public function generate(MovementPermit $permit): string
    {
        $permit->load(['sourceFarm.business', 'animals.animal', 'animals.livestock', 'transport', 'veterinaryApproval']);
        $qrImage = base64_encode((string) QrCode::format('png')->size(140)->margin(1)->generate($permit->verificationUrl() ?? $permit->permit_number));

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
