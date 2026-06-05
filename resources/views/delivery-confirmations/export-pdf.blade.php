<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Delivery confirmations report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>{{ __('Delivery confirmations report') }}</h1>
    <p>{{ __('Generated') }}: {{ $generatedAt->format('d M Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('Trip') }}</th>
                <th>{{ __('Received') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Receiver') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trips as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>#{{ $c->transport_trip_id }}</td>
                    <td>{{ $c->received_quantity }} {{ $c->received_unit }} — {{ $c->received_date->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($c->confirmation_status) }}</td>
                    <td>{{ $c->receiver_display }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
