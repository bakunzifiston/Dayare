<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Movement permit') }} {{ $permit->permit_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 12px; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 12px; margin-bottom: 16px; }
        .title { font-size: 22px; font-weight: bold; color: #1e3a8a; }
        .section { margin-top: 16px; }
        .section h3 { font-size: 13px; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.04em; }
        .grid td { padding: 4px 8px 4px 0; vertical-align: top; }
        .footer { margin-top: 24px; border-top: 1px solid #cbd5e1; padding-top: 12px; font-size: 10px; color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.data th { background: #f8fafc; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ __('Live animal movement permit') }}</div>
        <div>{{ __('Permit number') }}: {{ $permit->permit_number }}</div>
        <div>{{ __('Verify') }}: {{ $permit->verificationUrl() }}</div>
    </div>
    <table width="100%"><tr><td>
        <div class="section">
            <h3>{{ __('Movement details') }}</h3>
            <table class="grid">
                <tr><td><strong>{{ __('Type') }}</strong></td><td>{{ ucwords(str_replace('_', ' ', $permit->permit_type)) }}</td></tr>
                <tr><td><strong>{{ __('Origin') }}</strong></td><td>{{ $permit->origin_location ?: $permit->sourceFarm?->name }}</td></tr>
                <tr><td><strong>{{ __('Destination') }}</strong></td><td>{{ $permit->destination_location ?: '—' }}</td></tr>
                <tr><td><strong>{{ __('Departure') }}</strong></td><td>{{ $permit->departure_date?->toDateString() }}</td></tr>
                <tr><td><strong>{{ __('Expected arrival') }}</strong></td><td>{{ $permit->expected_arrival_date?->toDateString() }}</td></tr>
                <tr><td><strong>{{ __('Permit status') }}</strong></td><td>{{ ucwords(str_replace('_', ' ', $permit->permit_status)) }}</td></tr>
                <tr><td><strong>{{ __('Veterinary status') }}</strong></td><td>{{ ucwords(str_replace('_', ' ', $permit->veterinary_status)) }}</td></tr>
            </table>
        </div>
        <div class="section">
            <h3>{{ __('Transport') }}</h3>
            <table class="grid">
                <tr><td><strong>{{ __('Vehicle') }}</strong></td><td>{{ $permit->transport?->vehicle_number ?: $permit->vehicle_plate ?: '—' }}</td></tr>
                <tr><td><strong>{{ __('Driver') }}</strong></td><td>{{ $permit->transport?->driver_name ?: $permit->driver_name ?: '—' }}</td></tr>
                <tr><td><strong>{{ __('Transporter') }}</strong></td><td>{{ $permit->transport?->transporter_company ?: $permit->transporter_name ?: '—' }}</td></tr>
                <tr><td><strong>{{ __('Route') }}</strong></td><td>{{ $permit->transport?->route_information ?: '—' }}</td></tr>
            </table>
        </div>
        <div class="section">
            <h3>{{ __('Veterinary approval') }}</h3>
            <table class="grid">
                <tr><td><strong>{{ __('Veterinarian') }}</strong></td><td>{{ $permit->veterinaryApproval?->veterinarian_name ?: '—' }}</td></tr>
                <tr><td><strong>{{ __('Inspection date') }}</strong></td><td>{{ $permit->veterinaryApproval?->inspection_date?->toDateString() ?: '—' }}</td></tr>
                <tr><td><strong>{{ __('Result') }}</strong></td><td>{{ $permit->veterinaryApproval?->inspection_result ? ucwords(str_replace('_', ' ', $permit->veterinaryApproval->inspection_result)) : '—' }}</td></tr>
                <tr><td><strong>{{ __('Approval') }}</strong></td><td>{{ $permit->veterinaryApproval?->approval_status ? ucfirst($permit->veterinaryApproval->approval_status) : __('Pending') }}</td></tr>
            </table>
        </div>
        <div class="section">
            <h3>{{ __('Animals') }}</h3>
            <table class="data">
                <thead><tr><th>{{ __('Animal') }}</th><th>{{ __('Identifier') }}</th><th>{{ __('Condition') }}</th><th>{{ __('Qty') }}</th></tr></thead>
                <tbody>
                    @foreach ($permit->animals as $line)
                        <tr>
                            <td>{{ $line->animal?->animal_code ?: '—' }}</td>
                            <td>{{ $line->animal_identifier ?: $line->animal?->tag_number ?: '—' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $line->movement_condition)) }}</td>
                            <td>{{ $line->quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="section">
            <h3>{{ __('Signatures') }}</h3>
            <p>{{ __('Issued by') }}: {{ $permit->issued_by ?: '—' }}</p>
            <p>{{ __('Approved by') }}: {{ $permit->approver?->name ?: '—' }}</p>
            <p>{{ __('Issue date') }}: {{ $permit->issue_date?->toDateString() }}</p>
            <p>{{ __('Expiry date') }}: {{ $permit->expiry_date?->toDateString() ?: '—' }}</p>
        </div>
    </td><td width="160" align="right"><img src="{{ $qrImage }}" width="140" height="140" alt="QR"></td></tr></table>
    <div class="footer">{{ __('This movement permit authorizes live animal transport only and can be verified through the DayareMeat traceability portal.') }}</div>
</body>
</html>
