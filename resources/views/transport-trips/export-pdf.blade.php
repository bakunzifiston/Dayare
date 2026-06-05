<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Transport trips report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #f8fafc; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ __('Transport trips report') }}</h1>
    <p class="meta">{{ __('Generated') }}: {{ $generatedAt->format('d M Y H:i') }} @if($generatedBy)— {{ $generatedBy }}@endif</p>
    <table>
        <thead>
            <tr>
                <th>{{ __('Trip') }}</th>
                <th>{{ __('Certificate') }}</th>
                <th>{{ __('Route') }}</th>
                <th>{{ __('Vehicle') }}</th>
                <th>{{ __('Departure') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trips as $trip)
                <tr>
                    <td>#{{ $trip->id }}</td>
                    <td>{{ $trip->certificate?->certificate_number ?? '—' }}</td>
                    <td>{{ $trip->originFacility?->facility_name }} → {{ $trip->destination_display }}</td>
                    <td>{{ $trip->vehicle_plate_number }}</td>
                    <td>{{ $trip->departure_date?->format('Y-m-d') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
