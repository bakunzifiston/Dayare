<?php

namespace App\Http\Controllers;

use App\Models\CertificateQr;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public traceability page – linked by QR code.
 * When QR is scanned, the viewer sees: animal origin, legally inspected?, where from?, who inspected?, certificate valid?, safe for sale?
 */
class TraceabilityController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $certificateQr = CertificateQr::where('slug', $slug)->firstOrFail();
        $certificateQr->load([
            'certificate.facility',
            'certificate.inspector',
            'certificate.batch.postMortemInspection',
            'certificate.batch.slaughterExecution.slaughterPlan.facility',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.country',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.province',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.district',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.sector',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.cell',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.village',
            'certificate.batch.slaughterExecution.slaughterPlan.anteMortemInspections',
            'certificate.batch.slaughterExecution.slaughterPlan.inspector',
        ]);

        $cert = $certificateQr->certificate;
        $batch = $cert->batch;
        $execution = $batch?->slaughterExecution;
        $plan = $execution?->slaughterPlan;
        $animalIntake = $plan?->animalIntake;
        $postMortem = $batch?->postMortemInspection;

        $facilityName = $cert->facility?->facility_name ?? '—';
        $inspectorName = $cert->inspector?->full_name ?? '—';
        $slaughterDate = $execution?->slaughter_time?->format('d M Y') ?? $plan?->slaughter_date?->format('d M Y') ?? '—';
        $batchCode = $batch?->batch_code ?? '—';
        $certificateNumber = $cert->certificate_number ?? ('#' . $cert->id);

        $certificateValid = $cert->status === \App\Models\Certificate::STATUS_ACTIVE
            && (! $cert->expiry_date || ! $cert->expiry_date->isPast());
        $hasAnteMortem = $plan && $plan->anteMortemInspections->isNotEmpty();
        $hasPostMortemApproved = $postMortem && $postMortem->approved_quantity > 0;
        $legallyInspected = $hasAnteMortem && $hasPostMortemApproved && $cert->id;
        $safeForSale = $certificateValid && $legallyInspected;

        $originLocation = null;
        if ($animalIntake) {
            $parts = array_filter([
                $animalIntake->village?->name,
                $animalIntake->sector?->name,
                $animalIntake->district?->name,
                $animalIntake->province?->name,
                $animalIntake->country?->name,
            ]);
            $originLocation = $parts ? implode(', ', $parts) : ($animalIntake->farm_name ?? '—');
        }

        return view('traceability.show', [
            'certificate' => $cert,
            'facilityName' => $facilityName,
            'inspectorName' => $inspectorName,
            'slaughterDate' => $slaughterDate,
            'batchCode' => $batchCode,
            'certificateNumber' => $certificateNumber,
            'animalIntake' => $animalIntake,
            'originLocation' => $originLocation,
            'certificateValid' => $certificateValid,
            'legallyInspected' => $legallyInspected,
            'safeForSale' => $safeForSale,
            'hasAnteMortem' => $hasAnteMortem,
            'hasPostMortemApproved' => $hasPostMortemApproved,
        ]);
    }
}
