<?php

namespace App\Http\Controllers;

use App\Models\CertificateQr;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public traceability page – linked by QR code.
 * Displays: Facility Name, Inspector Name, Slaughter Date, Batch Code, Certificate Number.
 */
class TraceabilityController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $certificateQr = CertificateQr::where('slug', $slug)->firstOrFail();
        $certificateQr->load([
            'certificate.facility',
            'certificate.inspector',
            'certificate.batch.slaughterExecution',
        ]);

        $cert = $certificateQr->certificate;
        $facilityName = $cert->facility?->facility_name ?? '—';
        $inspectorName = $cert->inspector?->full_name ?? '—';
        $slaughterDate = $cert->batch?->slaughterExecution?->slaughter_time?->format('d M Y') ?? '—';
        $batchCode = $cert->batch?->batch_code ?? '—';
        $certificateNumber = $cert->certificate_number ?? ('#' . $cert->id);

        return view('traceability.show', [
            'facilityName' => $facilityName,
            'inspectorName' => $inspectorName,
            'slaughterDate' => $slaughterDate,
            'batchCode' => $batchCode,
            'certificateNumber' => $certificateNumber,
        ]);
    }
}
