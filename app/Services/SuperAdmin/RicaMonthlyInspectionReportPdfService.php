<?php

namespace App\Services\SuperAdmin;

use App\Models\Facility;
use App\Support\DomPdf;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RicaMonthlyInspectionReportPdfService
{
    public function __construct(
        private readonly RicaMonthlyInspectionReportService $reportService,
    ) {}

    public function generate(Facility $facility, Carbon $periodStart, Carbon $periodEnd): PDF
    {
        $report = $this->reportService->build($facility, $periodStart, $periodEnd);

        return DomPdf::loadView('superadmin.rica.monthly-reports.pdf', [
            'facility' => $facility,
            'report' => $report,
        ])->setPaper('a4', 'portrait');
    }

    public function downloadFilename(Facility $facility, Carbon $periodStart): string
    {
        $slug = Str::slug($facility->facility_name) ?: 'slaughterhouse';

        return sprintf(
            'private-meat-inspection-report-%s-%s.pdf',
            $slug,
            $periodStart->format('Y-m')
        );
    }
}
