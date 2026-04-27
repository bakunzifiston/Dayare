<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate {{ $certificate->certificate_number ?: '#'.$certificate->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .sub { color: #4b5563; margin: 0 0 16px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; width: 50%; }
        .label { color: #6b7280; font-size: 10px; text-transform: uppercase; margin-bottom: 2px; }
        .value { font-size: 12px; font-weight: 600; }
        .footer { margin-top: 14px; color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $plan = $certificate->batch?->slaughterExecution?->slaughterPlan;
        $animalIntake = $plan?->animalIntake;
        $anteMortemCount = $plan?->anteMortemInspections?->count() ?? 0;
        $anteMortemApproved = $plan?->anteMortemInspections?->sum('number_approved') ?? 0;
        $anteMortemRejected = $plan?->anteMortemInspections?->sum('number_rejected') ?? 0;
        $postMortem = $certificate->batch?->postMortemInspection;
        $originLocationParts = array_filter([
            $animalIntake?->village?->name,
            $animalIntake?->cell?->name,
            $animalIntake?->sector?->name,
            $animalIntake?->district?->name,
            $animalIntake?->province?->name,
            $animalIntake?->country?->name,
        ]);
        $originLocation = !empty($originLocationParts) ? implode(', ', $originLocationParts) : '—';
        $originSourceName = trim((string) (($animalIntake?->supplier_firstname ?? '').' '.($animalIntake?->supplier_lastname ?? '')));
    @endphp

    <h1>Meat Inspection Certificate</h1>
    <p class="sub">Certificate {{ $certificate->certificate_number ?: '#'.$certificate->id }}</p>

    <table class="grid">
        <tr>
            <td>
                <div class="label">Batch</div>
                <div class="value">{{ $certificate->batch?->batch_code ?: '—' }}</div>
            </td>
            <td>
                <div class="label">Facility</div>
                <div class="value">{{ $certificate->facility?->facility_name ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Inspector</div>
                <div class="value">{{ $certificate->inspector?->full_name ?: '—' }}</div>
            </td>
            <td>
                <div class="label">Status</div>
                <div class="value">{{ ucfirst((string) $certificate->status) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Issue Date</div>
                <div class="value">{{ optional($certificate->issued_at)->format('Y-m-d') ?: '—' }}</div>
            </td>
            <td>
                <div class="label">Expiry Date</div>
                <div class="value">{{ optional($certificate->expiry_date)->format('Y-m-d') ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">Traceability URL</div>
                <div class="value">{{ $certificate->certificateQr?->trace_url ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Ante-Mortem Inspection</div>
                <div class="value">
                    @if ($anteMortemCount > 0)
                        Completed ({{ $anteMortemCount }} records)
                        <br>
                        Approved: {{ $anteMortemApproved }}, Rejected: {{ $anteMortemRejected }}
                    @else
                        —
                    @endif
                </div>
            </td>
            <td>
                <div class="label">Post-Mortem Inspection</div>
                <div class="value">
                    @if ($postMortem)
                        {{ ucfirst((string) ($postMortem->result ?? '—')) }}
                        <br>
                        Approved quantity: {{ $postMortem->approved_quantity ?? 0 }}
                    @else
                        —
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Animal Origin (Farm)</div>
                <div class="value">{{ $animalIntake?->farm_name ?: '—' }}</div>
            </td>
            <td>
                <div class="label">Animal Origin (Farmer/Supplier)</div>
                <div class="value">{{ $originSourceName !== '' ? $originSourceName : '—' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">Farm Location</div>
                <div class="value">{{ $originLocation }}</div>
            </td>
        </tr>
    </table>

    <p class="footer">Generated: {{ $generatedAt->format('Y-m-d H:i') }}</p>
</body>
</html>
