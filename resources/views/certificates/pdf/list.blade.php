<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificates Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { margin: 0 0 14px; color: #4b5563; }
        .filters { margin: 0 0 12px; }
        .filters span { margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-weight: 700; }
    </style>
</head>
<body>
    <h1>Certificates Export</h1>
    <p class="meta">Generated: {{ $generatedAt->format('Y-m-d H:i') }}</p>
    <p class="filters">
        <span>Search: {{ $filters['search'] ?: 'All' }}</span>
        <span>Status: {{ $filters['status'] ?: 'All' }}</span>
        <span>Facility ID: {{ $filters['facility_id'] ?: 'All' }}</span>
        <span>Issued from: {{ $filters['issued_from'] ?: 'Any' }}</span>
        <span>Issued to: {{ $filters['issued_to'] ?: 'Any' }}</span>
    </p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Certificate Number</th>
                <th>Batch</th>
                <th>Facility</th>
                <th>Inspector</th>
                <th>Issue Date</th>
                <th>Expiry Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $cert)
                <tr>
                    <td>{{ $cert->id }}</td>
                    <td>{{ $cert->certificate_number ?: '—' }}</td>
                    <td>{{ $cert->batch?->batch_code ?: '—' }}</td>
                    <td>{{ $cert->facility?->facility_name ?: '—' }}</td>
                    <td>{{ $cert->inspector?->full_name ?: '—' }}</td>
                    <td>{{ optional($cert->issued_at)->format('Y-m-d') ?: '—' }}</td>
                    <td>{{ optional($cert->expiry_date)->format('Y-m-d') ?: '—' }}</td>
                    <td>{{ ucfirst((string) $cert->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No certificates found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
