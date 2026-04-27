<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Traceability - {{ $certificateNumber }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .sub { color: #4b5563; margin: 0 0 14px; }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .yes { background: #dcfce7; color: #166534; }
        .no { background: #fee2e2; color: #991b1b; }
        .section { border: 1px solid #d1d5db; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        .section-title { font-size: 11px; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        td { border: 1px solid #e5e7eb; padding: 7px; vertical-align: top; }
        .label { font-size: 10px; color: #6b7280; text-transform: uppercase; }
        .value { font-size: 12px; font-weight: 600; margin-top: 2px; }
        .footer { margin-top: 14px; font-size: 10px; color: #6b7280; }
        .checklist th { text-align: left; font-size: 9px; text-transform: uppercase; color: #6b7280; padding: 4px; }
        .checklist td { font-size: 11px; padding: 4px; }
        .subh { font-size: 10px; font-weight: 700; color: #374151; margin: 8px 0 4px; }
    </style>
</head>
<body>
    <h1>Meat Traceability</h1>
    <p class="sub">Certificate: {{ $certificateNumber }}</p>

    <div class="section">
        <div class="section-title">Quick answers</div>
        <p><span class="label">Legally inspected</span> <span class="badge {{ $legallyInspected ? 'yes' : 'no' }}">{{ $legallyInspected ? 'Yes' : 'No' }}</span></p>
        <p><span class="label">Certificate valid</span> <span class="badge {{ $certificateValid ? 'yes' : 'no' }}">{{ $certificateValid ? 'Yes' : 'No' }}</span></p>
        <p><span class="label">Safe for sale</span> <span class="badge {{ $safeForSale ? 'yes' : 'no' }}">{{ $safeForSale ? 'Yes' : 'No' }}</span></p>
    </div>

    <div class="section">
        <div class="section-title">Where did it come from?</div>
        <table>
            <tr>
                <td><div class="label">Slaughter facility</div><div class="value">{{ $facilityName }}</div></td>
                <td><div class="label">Slaughter date</div><div class="value">{{ $slaughterDate }}</div></td>
            </tr>
            <tr>
                <td><div class="label">Batch</div><div class="value">{{ $batchCode }}</div></td>
                <td><div class="label">Farm location</div><div class="value">{{ $originLocation ?: '—' }}</div></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Certificate</div>
        <table>
            <tr>
                <td><div class="label">Inspector (certificate)</div><div class="value">{{ $inspectorName }}</div></td>
                <td><div class="label">Certificate status</div><div class="value">{{ ucfirst((string) $certificate->status) }}</div></td>
            </tr>
        </table>
    </div>

    @if (!empty($anteMortemInspectionsDetail))
        <div class="section">
            <div class="section-title">Ante-mortem — checklist</div>
            @foreach ($anteMortemInspectionsDetail as $idx => $am)
                <p><strong>Record {{ $idx + 1 }}</strong> — {{ $am['inspection_date'] }} · {{ $am['species'] }}
                    @if (!empty($am['inspector'])) · Inspector: {{ $am['inspector'] }} @endif
                </p>
                <p class="sub">Examined / approved / rejected: {{ $am['number_examined'] }} / {{ $am['number_approved'] }} / {{ $am['number_rejected'] }}</p>
                @if (!empty($am['notes']))
                    <p class="sub" style="white-space: pre-wrap;">{{ $am['notes'] }}</p>
                @endif
                @if (empty($am['rows']))
                    <p class="sub">No checklist line items recorded.</p>
                @else
                    <table class="checklist">
                        <tr>
                            <th>Item</th>
                            <th>Result</th>
                            <th>Notes</th>
                        </tr>
                        @foreach ($am['rows'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['value'] }}</td>
                                <td>{{ $row['notes'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
                @if (!$loop->last)<div style="height:6px"></div>@endif
            @endforeach
        </div>
    @endif

    @if (!empty($postMortemInspectionDetail))
        @php $pm = $postMortemInspectionDetail; @endphp
        <div class="section">
            <div class="section-title">Post-mortem — checklist</div>
            <p><strong>{{ $pm['inspection_date'] }}</strong> — {{ $pm['result'] }} · {{ $pm['species'] }}
                @if (!empty($pm['inspector'])) · Inspector: {{ $pm['inspector'] }} @endif
            </p>
            <p class="sub">Total examined / approved / condemned: {{ $pm['total_examined'] }} / {{ $pm['approved_quantity'] }} / {{ $pm['condemned_quantity'] }}</p>
            @if (!empty($pm['notes']))
                <p class="sub" style="white-space: pre-wrap;">{{ $pm['notes'] }}</p>
            @endif
            <p class="subh">Carcass inspection</p>
            @if (empty($pm['carcass_rows']))
                <p class="sub">No carcass observations recorded.</p>
            @else
                <table class="checklist">
                    <tr><th>Item</th><th>Status</th><th>Notes</th></tr>
                    @foreach ($pm['carcass_rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['value'] }}</td>
                            <td>{{ $row['notes'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
            <p class="subh">Organ inspection</p>
            @if (empty($pm['organ_rows']))
                <p class="sub">No organ observations recorded.</p>
            @else
                <table class="checklist">
                    <tr><th>Item</th><th>Status</th><th>Notes</th></tr>
                    @foreach ($pm['organ_rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['value'] }}</td>
                            <td>{{ $row['notes'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    @endif

    <p class="footer">Generated: {{ $generatedAt->format('Y-m-d H:i') }}</p>
</body>
</html>
