<?php

namespace App\Http\Controllers;

use App\Models\AnteMortemInspection;
use App\Models\CertificateQr;
use App\Models\PostMortemInspection;
use App\Support\AnteMortemChecklist;
use App\Support\PostMortemChecklist;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public traceability page – linked by QR code.
 * When QR is scanned, the viewer sees: animal origin, legally inspected?, where from?, who inspected?, certificate valid?, safe for sale?
 */
class TraceabilityController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function traceabilityPayload(string $slug): array
    {
        $certificateQr = CertificateQr::where('slug', $slug)->firstOrFail();
        $certificateQr->load([
            'certificate.facility',
            'certificate.inspector',
            'certificate.batch.postMortemInspection.inspector',
            'certificate.batch.postMortemInspection.observations',
            'certificate.batch.slaughterExecution.slaughterPlan.facility',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.country',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.province',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.district',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.sector',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.cell',
            'certificate.batch.slaughterExecution.slaughterPlan.animalIntake.village',
            'certificate.batch.slaughterExecution.slaughterPlan.anteMortemInspections.inspector',
            'certificate.batch.slaughterExecution.slaughterPlan.anteMortemInspections.observations',
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
        $certificateNumber = $cert->certificate_number ?? ('#'.$cert->id);

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
                $animalIntake->cell?->name,
                $animalIntake->sector?->name,
                $animalIntake->district?->name,
                $animalIntake->province?->name,
                $animalIntake->country?->name,
            ]);
            $originLocation = $parts ? implode(', ', $parts) : null;
        }

        $anteMortemInspectionsDetail = $this->formatAnteMortemInspectionsForTrace($plan?->anteMortemInspections ?? collect());
        $postMortemInspectionDetail = $this->formatPostMortemInspectionForTrace($postMortem);

        return [
            'certificate' => $cert,
            'certificateQr' => $certificateQr,
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
            'anteMortemInspectionsDetail' => $anteMortemInspectionsDetail,
            'postMortemInspectionDetail' => $postMortemInspectionDetail,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatAnteMortemInspectionsForTrace(Collection $inspections): array
    {
        return $inspections->map(function (AnteMortemInspection $inspection) {
            $items = AnteMortemChecklist::itemsForSpecies($inspection->species);
            $rows = $inspection->observations->map(function ($obs) use ($items) {
                $label = $items[$obs->item]['label'] ?? Str::of($obs->item)->replace('_', ' ')->title()->toString();

                return [
                    'label' => $label,
                    'value' => $this->humanizeChecklistValue((string) $obs->value),
                    'notes' => $obs->notes ? (string) $obs->notes : null,
                ];
            })->values()->all();

            return [
                'inspection_date' => $inspection->inspection_date?->format('d M Y') ?? '—',
                'species' => $inspection->species ?? '—',
                'number_examined' => $inspection->number_examined,
                'number_approved' => $inspection->number_approved,
                'number_rejected' => $inspection->number_rejected,
                'notes' => $inspection->notes ? (string) $inspection->notes : null,
                'inspector' => $inspection->inspector?->full_name,
                'rows' => $rows,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatPostMortemInspectionForTrace(?PostMortemInspection $inspection): ?array
    {
        if (! $inspection) {
            return null;
        }

        $species = $inspection->species;
        $items = PostMortemChecklist::itemsForSpecies($species);

        $mapRows = function (Collection $observations) use ($items) {
            return $observations->map(function ($obs) use ($items) {
                $label = $items[$obs->item]['label'] ?? Str::of($obs->item)->replace('_', ' ')->title()->toString();
                $critical = (bool) ($items[$obs->item]['critical'] ?? false);

                return [
                    'label' => $label,
                    'value' => $this->humanizeChecklistValue((string) $obs->value),
                    'notes' => $obs->notes ? (string) $obs->notes : null,
                    'critical' => $critical,
                ];
            })->values()->all();
        };

        $carcass = $inspection->observations->where('category', 'carcass');
        $organs = $inspection->observations->where('category', 'organ');

        return [
            'inspection_date' => $inspection->inspection_date?->format('d M Y') ?? '—',
            'species' => $inspection->species ?? '—',
            'result' => $inspection->result ? ucfirst((string) $inspection->result) : '—',
            'total_examined' => $inspection->total_examined,
            'approved_quantity' => $inspection->approved_quantity,
            'condemned_quantity' => $inspection->condemned_quantity,
            'notes' => $inspection->notes ? (string) $inspection->notes : null,
            'inspector' => $inspection->inspector?->full_name,
            'carcass_rows' => $mapRows($carcass),
            'organ_rows' => $mapRows($organs),
        ];
    }

    private function humanizeChecklistValue(string $value): string
    {
        return Str::of($value)->replace('_', ' ')->title()->toString();
    }

    public function show(Request $request, string $slug): View
    {
        return view('traceability.show', $this->traceabilityPayload($slug));
    }

    public function exportPdf(Request $request, string $slug): Response
    {
        $data = $this->traceabilityPayload($slug);
        $pdf = Pdf::loadView('traceability.pdf', [
            ...$data,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $fileName = 'traceability-'.preg_replace('/[^A-Za-z0-9_-]/', '', $slug).'.pdf';

        return $pdf->download($fileName);
    }
}
